<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Exceptions\LocalizedException;
use Formwork\Admin\Image;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\Users\User;
use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;

class Users extends AbstractController
{
    public function index()
    {
        $this->modal('newUser');

        $this->modal('deleteUser');

        $this->view('admin', array(
            'title' => $this->label('users.users'),
            'content' => $this->view('users.index', array(
                'users' => Admin::instance()->users()
            ), false)
        ));
    }

    public function create()
    {
        $data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        foreach (array('username', 'fullname', 'password', 'email', 'language') as $var) {
            if (!$data->has($var)) {
                $this->notify($this->label('users.user.cannot-create.var-missing', $var), 'error');
                $this->redirect('/users/', 302, true);
            }
        }

        // Ensure there isn't a user with the same username
        if (Admin::instance()->users()->has($data->get('username'))) {
            $this->notify($this->label('users.user.cannot-create.already-exists'), 'error');
            $this->redirect('/users/', 302, true);
        }

        $userData = array(
            'username' => $data->get('username'),
            'fullname' => $data->get('fullname'),
            'hash'     => Password::hash($data->get('password')),
            'email'    => $data->get('email'),
            'language' => $data->get('language'),
            'avatar'   => null
        );

        FileSystem::write(ACCOUNTS_PATH . $data->get('username') . '.yml', YAML::encode($userData));

        $this->notify($this->label('users.user.created'), 'success');
        $this->redirect('/users/', 302, true);
    }

    public function delete(RouteParams $params)
    {
        try {
            $user = Admin::instance()->users()->get($params->get('user'));
            if (!$user) {
                throw new LocalizedException('User ' . $params->get('user') . ' not found', 'users.user.not-found');
            }
            if ($user->isLogged()) {
                throw new LocalizedException('Cannot delete currently logged user', 'users.user.cannot-delete.logged');
            }
            $this->deleteAvatar($user);
            FileSystem::delete(ACCOUNTS_PATH . $params->get('user') . '.yml');
            $this->registry('lastAccess')->remove($params->get('user'));
            $this->notify($this->label('users.user.deleted'), 'success');
            $this->redirect('/users/', 302, true);
        } catch (LocalizedException $e) {
            $this->notify($e->getLocalizedMessage(), 'error');
            $this->redirect('/users/', 302, true);
        }
    }

    public function profile(RouteParams $params)
    {
        $user = Admin::instance()->users()->get($params->get('user'));

        if (is_null($user)) {
            $this->notify($this->label('users.user.not-found'), 'error');
            $this->redirect('/users/', 302, true);
        }

        if (HTTPRequest::method() === 'POST') {
            $data = $user->toArray();

            $postData = HTTPRequest::postData();

            unset($postData['csrf-token']);

            if (!empty($postData['password'])) {
                if (!$user->isLogged()) {
                    $this->notify($this->label('users.user.cannot-change-password'), 'error');
                    $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
                }
                $postData['hash'] = Password::hash($postData['password']);
                unset($postData['password']);
            }

            foreach ($postData as $key => $value) {
                if (!empty($value)) {
                    $data[$key] = $value;
                }
            }

            if (HTTPRequest::hasFiles()) {
                $avatarsPath = ADMIN_PATH . 'avatars' . DS;
                $uploader = new Uploader(
                    $avatarsPath,
                    array('allowedMimeTypes' => array('image/gif', 'image/jpeg', 'image/png'))
                );
                try {
                    if ($uploader->upload(FileSystem::randomName())) {
                        $avatarSize = Formwork::instance()->option('admin.avatar_size');
                        $image = new Image($avatarsPath . $uploader->uploadedFiles()[0]);
                        $image->square($avatarSize)->save();
                        $this->deleteAvatar($user);
                        $data['avatar'] = $uploader->uploadedFiles()[0];
                        $this->notify($this->label('user.avatar.uploaded'), 'success');
                    }
                } catch (LocalizedException $e) {
                    $this->notify($this->label('uploader.error', $e->getLocalizedMessage()), 'error');
                    $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
                }
            }
            
            FileSystem::write(ACCOUNTS_PATH . $data['username'] . '.yml', YAML::encode($data));

            $this->notify($this->label('users.user.edited'), 'success');
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }

        $this->modal('changes');

        $this->view('admin', array(
            'title' => $this->label('users.user-profile', $user->username()),
            'content' => $this->view('users.profile', array(
                'user' => $user
            ), false)
        ));
    }

    protected function deleteAvatar(User $user)
    {
        $avatar = $user->avatar()->path();
        if (FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
