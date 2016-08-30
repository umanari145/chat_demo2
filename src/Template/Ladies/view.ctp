<div class="ladies view large-12 medium-12  columns content">

    <?php echo $this->Form->create( null, [ 'url' => ['controller' => 'Comments', 'action' => 'add']] ); ?>

    <h3><?= h($lady->name) ?></h3>

    <div class="small-offset-3 large-6 medium-6">
        <?php echo $this->Form->input('ladies_id',['type' => 'hidden','value' => h($lady->id)]); ?>

        <div class="view_name">
            <span><?= h($lady->name) ?></span>
        </div>


        <?php echo $this->Html->image( $lady->image_url, [
                   'class' => 'view_img',
                   'alt'   => $lady->name
                  ]);
        ?>

        <div class="to_supplier" >
         <?php echo $this->Html->link( "この子とチャットする" , $lady->url ,['target' =>'_blank','class'=>'button to_dmm_button']);
         ?>
        </div>

        <div class="category">
            <?php
               if( !empty($lady->category) ) echo h($dmm_category_list[$lady->category]);
            ?>
        </div>

        <div class="profile">
            <?= $lady->profile ?>
        </div>

        <div class="to_supplier" >
         <?php echo $this->Html->link( "この子とチャットする" , $lady->url ,['target' =>'_blank','class'=>'button to_dmm_button']);
         ?>
        </div>

        <div class="comment_add">
            <?php echo $this->Form->input('comment',['type'=>'textarea']); ?>
        </div>
        <?= $this->Form->button(__('コメントを書き込む')) ?>

        <div class="comment_list">
           <?php foreach ($comments as $comment): ?>
           <div class="comment_each">
               <p class="comment_contents"><?php echo $comment['comment']; ?></p>
               <span class="comment_date"><?php echo $comment['created']; ?></span>
           </div>
           <?php endforeach; ?>
        </div>

    <?= $this->Form->end() ?>

</div>
