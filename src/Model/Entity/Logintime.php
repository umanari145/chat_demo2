<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Logintime Entity
 *
 * @property int $id
 * @property int $ladies_id
 * @property int $working_status
 * @property \Cake\I18n\Time $login_start_time
 * @property \Cake\I18n\Time $login_end_time
 * @property int $login_status
 * @property bool $is_delete
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \App\Model\Entity\Lady $lady
 */
class Logintime extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
