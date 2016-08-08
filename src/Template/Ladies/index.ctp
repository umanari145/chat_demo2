

<div class="users index large-12 medium-12 columns content">
    <h3>チャットレディ一覧画面</h3>
    <div class="small-offset-1 large-10">
        <?php echo $this->element('search'); ?>
        <?php foreach ($ladies as $lady): ?>
        <div class="index_item large-2 medium-2">
           <?php echo $this->Html->image( $lady->image_url, [
                   'class' => 'index_img',
                   'alt'   => $lady->name,
                   'url'   => ['controller' => 'ladies', 'action' => 'view', $lady->id]
                  ]);
            ?>
            <div class="index_name">
                <?= $this->Html->link( $lady->name , ['action' => 'view', $lady->id]) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->numbers(['first'=>2 ,'last' => 2]) ?>
        </ul>
        <p><?= $this->Paginator->counter() ?></p>
    </div>
</div>
