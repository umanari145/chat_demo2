<div class="ladies view large-12 medium-12  columns content">


    <h3><?= h($lady->name) ?></h3>

    <div class="small-offset-3 large-6 medium-6">
        <div class="view_name">
            <td><?= h($lady->name) ?></td>
        </div>
        <?php echo $this->Html->image( $lady->image_url, [
                   'class' => 'view_img',
                   'alt'   => $lady->name
                  ]);
        ?>

        <div class="to_supplier" >
         <?php echo $this->Html->link( "この子とチャットする" , $lady->url ,['target' =>'_blank']);
         ?>
        </div>

</div>
