<div class="users index large-12 medium-12 columns content">
    <h3>チャットレディ一覧画面</h3>

    <div class="large-10">
        <?php foreach ($users as $user): ?>
        <div class="large-2 medium-2">
           <?php echo $this->Html->image( $user->image_url, [
                   'class' => 'index_img',
                   'alt'   => $user->name,
                   'url'   => ['controller' => 'users', 'action' => 'view', $user->id]
                  ]);
            ?>
            <div class="index_name">
                <?= $this->Html->link( $user->name , ['action' => 'view', $user->id]) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->prev('< ' . __('前のページへ')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('次のページ') . ' >') ?>
        </ul>
        <p><?= $this->Paginator->counter() ?></p>
    </div>
</div>
