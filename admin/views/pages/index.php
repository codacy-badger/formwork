<div class="component">
    <h3 class="caption"><?= $this->label('pages.pages') ?></h3>
<?php
    if ($this->user()->permissions()->has('pages.create')):
?>
    <button type="button" data-modal="newPageModal"><i class="i-plus-circle"></i> <?= $this->label('pages.new-page') ?></button>
<?php
    endif;
?>
    <button type="button" data-command="expand-all-pages"><i class="i-chevron-down"></i> <?= $this->label('pages.pages.expand-all') ?></button>
    <button type="button" data-command="collapse-all-pages"><i class="i-chevron-up"></i> <?= $this->label('pages.pages.collapse-all') ?></button>
    <div class="separator"></div>
    <input class="page-search" type="search" placeholder="<?= $this->label('pages.pages.search') ?>">
    <div class="separator"></div>
    <?= $pagesList ?>
</div>
