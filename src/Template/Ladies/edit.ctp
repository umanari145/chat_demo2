<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $lady->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $lady->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Ladies'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="ladies form large-9 medium-8 columns content">
    <?= $this->Form->create($lady) ?>
    <fieldset>
        <legend><?= __('Edit Lady') ?></legend>
        <?php
            echo $this->Form->input('code');
            echo $this->Form->input('name');
            echo $this->Form->input('image_url');
            echo $this->Form->input('url');
            echo $this->Form->input('is_delete');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
