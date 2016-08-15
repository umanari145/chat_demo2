<div class="search_box clearfix" >
    <?= $this->Form->create('ladies',['type'=>'get']) ?>
        <?php
            echo $this->Form->input('keyword',['label' => false , 'class'=>'small_box']);
        ?>
    <?= $this->Form->button('検索',['class'=>'button']) ?>
    <?= $this->Form->end() ?>
</div>