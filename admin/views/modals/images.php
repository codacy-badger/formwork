<div id="imagesModal" class="modal">
    <div class="modal-content modal-size-large">
        <h3 class="caption"><?= $this->label('modal.images.title') ?></h3>
        <div class="image-picker-empty-state">
            <i class="image-picker-empty-state-icon i-images"></i>
            <h4 class="h5"><?= $this->label('modal.images.no-images') ?></h4>
<?php
            if ($this->user()->permissions()->has('pages.upload_files')):
?>
            <p><?= $this->label('modal.images.no-images.upload') ?></p>
            <button type="button" data-command="upload" data-upload-target="file-uploader"><i class="i-cloud-upload"></i> <?= $this->label('modal.action.upload-file') ?></button>
<?php
            endif;
?>
        </div>
        <select class="image-picker">
<?php
        foreach ($page->images() as $image):
?>
            <option value="<?= $this->pageUri($page) . $image ?>"><?= $image ?></option>
<?php
        endforeach;
?>
        </select>
        <button type="button" data-dismiss="imagesModal"><?= $this->label('modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right image-picker-confirm" data-dismiss="imagesModal"><?= $this->label('modal.action.continue') ?></button>
    </div>
</div>
