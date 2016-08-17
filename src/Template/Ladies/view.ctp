<div class="ladies view large-12 medium-12  columns content">


    <h3><?= h($lady->name) ?></h3>

    <div class="small-offset-3 large-6 medium-6">
        <div class="view_name">
            <span><?= h($lady->name) ?></span>
        </div>


        <?php echo $this->Html->image( $lady->image_url, [
                   'class' => 'view_img',
                   'alt'   => $lady->name
                  ]);
        ?>

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

</div>
