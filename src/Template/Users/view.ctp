<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete User'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # {0}?', $user->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Users'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New User'), ['action' => 'add']) ?> </li>
        <li><?= $this->Html->link(__('List Logintimes'), ['controller' => 'Logintimes', 'action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Logintime'), ['controller' => 'Logintimes', 'action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="users view large-9 medium-8 columns content">
    <h3><?= h($user->name) ?></h3>
    <table class="vertical-table">
        <tr>
            <th><?= __('Code') ?></th>
            <td><?= h($user->code) ?></td>
        </tr>
        <tr>
            <th><?= __('Name') ?></th>
            <td><?= h($user->name) ?></td>
        </tr>
        <tr>
            <th><?= __('Image Url') ?></th>
            <td><?= h($user->image_url) ?></td>
        </tr>
        <tr>
            <th><?= __('Url') ?></th>
            <td><?= h($user->url) ?></td>
        </tr>
        <tr>
            <th><?= __('Id') ?></th>
            <td><?= $this->Number->format($user->id) ?></td>
        </tr>
        <tr>
            <th><?= __('Created') ?></th>
            <td><?= h($user->created) ?></td>
        </tr>
        <tr>
            <th><?= __('Modified') ?></th>
            <td><?= h($user->modified) ?></td>
        </tr>
        <tr>
            <th><?= __('Is Delete') ?></th>
            <td><?= $user->is_delete ? __('Yes') : __('No'); ?></td>
        </tr>
    </table>
    <div class="related">
        <h4><?= __('Related Logintimes') ?></h4>
        <?php if (!empty($user->logintimes)): ?>
        <table cellpadding="0" cellspacing="0">
            <tr>
                <th><?= __('Id') ?></th>
                <th><?= __('User Id') ?></th>
                <th><?= __('Working Status') ?></th>
                <th><?= __('Login Start Time') ?></th>
                <th><?= __('Login End Time') ?></th>
                <th><?= __('Login Status') ?></th>
                <th><?= __('Is Delete') ?></th>
                <th><?= __('Created') ?></th>
                <th><?= __('Modified') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
            <?php foreach ($user->logintimes as $logintimes): ?>
            <tr>
                <td><?= h($logintimes->id) ?></td>
                <td><?= h($logintimes->user_id) ?></td>
                <td><?= h($logintimes->working_status) ?></td>
                <td><?= h($logintimes->login_start_time) ?></td>
                <td><?= h($logintimes->login_end_time) ?></td>
                <td><?= h($logintimes->login_status) ?></td>
                <td><?= h($logintimes->is_delete) ?></td>
                <td><?= h($logintimes->created) ?></td>
                <td><?= h($logintimes->modified) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['controller' => 'Logintimes', 'action' => 'view', $logintimes->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['controller' => 'Logintimes', 'action' => 'edit', $logintimes->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['controller' => 'Logintimes', 'action' => 'delete', $logintimes->id], ['confirm' => __('Are you sure you want to delete # {0}?', $logintimes->id)]) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
