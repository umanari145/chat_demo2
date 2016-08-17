<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Util\Constant;
use Cake\Collection\Collection;

/**
 * Logintimes Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Ladies
 *
 * @method \App\Model\Entity\Logintime get($primaryKey, $options = [])
 * @method \App\Model\Entity\Logintime newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Logintime[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Logintime|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Logintime patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Logintime[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Logintime findOrCreate($search, callable $callback = null)
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LogintimesTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('logintimes');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

//        $this->belongsTo('Ladies', [
//            'foreignKey' => 'ladies_id'
//        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('working_status')
            ->allowEmpty('working_status');

        $validator
            ->dateTime('login_start_time')
            ->allowEmpty('login_start_time');

        $validator
            ->dateTime('login_end_time')
            ->allowEmpty('login_end_time');

        $validator
            ->integer('login_status')
            ->allowEmpty('login_status');

        $validator
            ->boolean('is_delete')
            ->requirePresence('is_delete', 'create')
            ->notEmpty('is_delete');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        //$rules->add($rules->existsIn(['ladies_id'], 'Ladies'));

        return $rules;
    }

    /**
     * ログイン状態と非ログイン状態のチャットレディを分ける
     *
     * @param unknown $ladyIdHashFinal ログインユーザーのcharacter_id
     * @param unknown $allCharacterIdList character_idのデータ
     * @return ログイン/非ログインごとのユーザーのデータ
     */
    public function getLoginUserList( $ladyIdHashFinal, $allCharacterIdList ) {

        $UserList =[
                'login'        => [],
                'not_login'    => []
        ];

        foreach ( $allCharacterIdList as $characterId ) {

            if( isset($ladyIdHashFinal[$characterId]) === true ) {
                #ログインしているユーザー
                $UserList['login'][] = [
                'character_id'   => $characterId,
                'working_status' => $this->getWorkingStatusNum( $ladyIdHashFinal[$characterId])
                ];
            } else {
                #ログインしていないユーザー
                $UserList['not_login'][] = [
                'character_id'   => $characterId
                ];
            }
        }
        return $UserList;
    }

    /**
     * ログイン時の状態を文字列から数字で返す
     *
     * @param string $working_status_str 文字列waiting(1),party(2),twoshot(3)
     * @return number 数値
     */
    private function getWorkingStatusNum( $working_status_str ="") {

        $working_status_num;
        switch( $working_status_str){
            case 'waiting':
                $working_status_num = Constant::WORKING_STATUS_WAITING;
                break;
            case 'party':
                $working_status_num = Constant::WORKING_STATUS_PARTY;
                break;
            case 'twoshot':
                $working_status_num = Constant::WORKING_STATUS_TWOSHOT ;
                break;
            default:
                break;
        }
        return $working_status_num;
    }

    /**
     * ログインデータを記録する
     *
     * @param unknown $userList ログイン者と非ログイン者のデータ
     */
    public function registLoginStaffData( $userList =[]){

        //$loginLadiesHashArr = $this->getLoginedLadiesHash();

        foreach ( $userList['login'] as $userData ) {
            $this->divLoginStatus( $userData ,true );
        }


        foreach ( $userList['not_login'] as $userData2 ) {
            $this->divLoginStatus( $userData2 ,false );
        }

    }

    /**
     * ログインステータスの判定とデータの更新
     *
     * @param unknown $userData ユーザーデータ(user_idの入ったデータ)
     * @param string $isLogin true(ログイン中)/false(ログインしていない)
     * @param $loginLadiesHashArr すでにログインしているチャットレディのハッシュid
     */
    private function divLoginStatus($userData = [], $isLogin = true /**$loginLadiesHashArr**/ ) {

        if ($isLogin === true) {
            //ログインユーザー
            //今現在ログインをしていて
            $hasLoginData = $this->hasLogin( $userData['character_id']);

            if( $hasLoginData !== false ) {
                //ステータス変更あり
                if( $hasLoginData['working_status'] != $userData['working_status'] ) {
                    //以前のステータス情報を閉じる
                    $this->updateUserLoginStatus( $hasLoginData, Constant::LOGIN_STATUS_FINISH );
                    //新規の記録の場合は何もしない
                    $this->updateUserLoginStatus( $userData, Constant::LOGIN_STATUS_START );
                }
                //ステータス変更ない場合は何もしない
            } else {
                //ログイン記録がない場合は新規の記録
                $this->updateUserLoginStatus( $userData, Constant::LOGIN_STATUS_START );
            }

        } else {
            //非ログインユーザー
            //今現在ログインをしていなくて前回処理時にログインがある→ログイン終了をする
            $hasLoginData = $this->hasLogin( $userData['character_id']);
            if( $hasLoginData !== false ) {
                $this->updateUserLoginStatus( $hasLoginData, Constant::LOGIN_STATUS_FINISH );
            }
        }

    }

    /**
     * ログイン中か否か
     *
     * @param string $character_id キャラクターID
     * @return boolean true(ログイン中) / false (ログインしていない)
     */
    private function hasLogin( $character_id = null ) {

        $hasLoginData = $this->find ()
                        ->select()
                        ->where(['ladies_id' => $character_id])
                        ->where(['login_status' => Constant::LOGIN_STATUS_START])
                        ->hydrate(false)
                        ->toList();

        return ( count($hasLoginData) > 0 ) ? $hasLoginData[0] : false;
    }


    /**
     * すでにログインしているチャットレディのidをハッシュで格納する
     *
     * @return ladies_idのハッシュ
     */
    private function getLoginedLadiesHash(){

        $logintimes = TableRegistry::get('Logintimes');

        $loginLadiesHashArr = $logintimes->find()
        ->select(['ladies_id'])
        ->where(['login_status' => Constant::LOGIN_STATUS_START])
        ->hydrate(false)
        ->toList();

    }



    /**
     * ログインステータスを更新する(新規ログインの開始/既存ログインの終了)
     *
     * @param string $userData (新規ログイン開始時はcharacter_d / 既存ログイン終了時はLogintimeのid)
     * @param string $status 1=ログイン開始 2=ログイン終了
     * @return true(成功) / false (失敗)
     */
    private function updateUserLoginStatus( $userData = null, $status ="" ) {
        $data = [
                'login_status'     => $status
        ];

        if( empty($status) ) return false;

        if( empty( $userData['character_id']) && empty($userData['id']) ) {
            return false;
        }

        $logintimes       = TableRegistry::get('Logintimes');

        switch( $status ){
            case Constant::LOGIN_STATUS_START:
                //新規ログイン記録
                //userDataはUserから取得したデータ
                $data['ladies_id']        = $userData['character_id'];
                $data['login_start_time'] = date('Y-m-d H:i:s');
                $data['login_status']     = Constant::LOGIN_STATUS_START;
                $data['working_status']   = $userData['working_status'];
                $data['is_delete'] = false;
                $loginEntitity     = $logintimes->newEntity( $data );

                break;
            case Constant::LOGIN_STATUS_FINISH:
                //ログイン終了
                //userDataはログイン中のLogintimeのデータ
                $loginEntitity = $logintimes->get($userData['id']);
                $loginEntitity ->login_end_time = date('Y-m-d H:i:s');
                $loginEntitity ->login_status   =  Constant::LOGIN_STATUS_FINISH;
                break;

            default:
                break;
        }

        $logintimes->save( $loginEntitity  );
        return true;
    }


}
