<div class="search_box clearfix" >

        <?php
          echo $this->Paginator->counter(
                 '{{count}} 件中  {{start}} 件 ～ {{end}} 件'
            );?>

    <?php if( !empty($searchWord)): ?>
    <span>検索語句 : <?= h($searchWord) ?></span>
    <?php endif; ?>

    <?= $this->Form->create('ladies',['type'=>'get']) ?>
        <?php
            echo $this->Form->input('keyword',['label' => false , 'class'=>'small_box']);
        ?>
    <?= $this->Form->button('検索',['class'=>'button']) ?>
    <?= $this->Form->end() ?>
</div>