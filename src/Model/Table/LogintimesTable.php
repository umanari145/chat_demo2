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

        $loginLadiesHash = $this->getPreLoginedLadiesHash();
        $loginDataArr =[];

        foreach ( $userList['login'] as $userData ) {
            $this->divLoginStatus( $userData ,true, $loginLadiesHash, $loginDataArr );
        }

        foreach ( $userList['not_login'] as $userData2 ) {
            $this->divLoginStatus( $userData2 ,false, $loginLadiesHash, $loginDataArr );
        }
        $this->saveLoginEntity( $loginDataArr );
    }

    /**
     * ログインステータスの判定とデータの更新
     *
     * @param unknown $userData ユーザーデータ(user_idの入ったデータ)
     * @param string $isLogin true(ログイン中)/false(ログインしていない)
     * @param hash $loginLadiesHash すでにログインしているチャットレディのハッシュ[character_idがkey]
     * @param arrray $loginDataArr エンティティの配列
     */
    private function divLoginStatus( $userData = [], $isLogin = true , $loginLadiesHash, &$loginDataArr ) {

        if ($isLogin === true) {
            //ログインユーザー
            //今現在ログインをしていて
            $hasLoginData = ( !empty( $loginLadiesHash[$userData['character_id']] ) ) ? $loginLadiesHash[$userData['character_id']] :false;

            if( $hasLoginData !== false ) {
                //ステータス変更あり
                if( $hasLoginData['working_status'] != $userData['working_status'] ) {
                    //以前のステータス情報を閉じる
                    $loginDataArr[] = $this->updateUserLoginStatus( $hasLoginData, Constant::LOGIN_STATUS_FINISH );
                    //新規の記録(正確には新規ステータス)のスタート
                    $loginDataArr[] = $this->updateUserLoginStatus( $userData, Constant::LOGIN_STATUS_START );
                }
                //ステータス変更ない場合は何もしない
            } else {
                //ログイン記録がない場合は新規の記録
                $loginDataArr[] = $this->updateUserLoginStatus( $userData, Constant::LOGIN_STATUS_START );
            }

        } else {
            //非ログインユーザー
            //今現在ログインをしていなくて前回処理時にログインがある→ログイン終了をする
            $hasLoginData = ( !empty( $loginLadiesHash[$userData['character_id']] ) ) ? $loginLadiesHash[$userData['character_id']] :false;

            if( $hasLoginData !== false ) {
                $loginDataArr[] = $this->updateUserLoginStatus( $hasLoginData, Constant::LOGIN_STATUS_FINISH );
            }
        }

    }

    /**
     * 前回ログインしているチャットレディのidをハッシュで格納する
     *
     * @return hash ladies_id => logintimesのid のハッシュ
     */
    private function getPreLoginedLadiesHash(){

        $logintimes = TableRegistry::get('Logintimes');

        $loginLadiesHashArr = $logintimes->find()
        ->select(['id','ladies_id','working_status'])
        ->where(['login_status' => Constant::LOGIN_STATUS_START])
        ->where(['is_delete'    => false ])
        ->hydrate(false)
        ->toList();

        $loginLadiesHash;
        foreach ( $loginLadiesHashArr as $hash) {
            $loginLadiesHash[$hash['ladies_id']] = [
                 'id'             => $hash['id'],
                 'working_status' => $hash['working_status']
            ];
        }

        return $loginLadiesHash;
    }



    /**
     * ログインステータスを更新する(新規ログインの開始/既存ログインの終了)
     *
     * @param string $record (新規ログイン開始時はcharacter_d / 既存ログイン終了時はLogintimeのid)
     * @param string $status 1=ログイン開始 2=ログイン終了
     * @return true(成功) / false (失敗)
     */
    private function updateUserLoginStatus( $record = null, $status ="" ) {

        if( empty($status) ) return false;

        if( empty( $record['character_id']) && empty($record['id']) ) {
            return false;
        }

        $loginData;
        switch( $status ){
            case Constant::LOGIN_STATUS_START:
                //新規ログイン記録
                //recordはUserから取得したデータ
                $loginData['ladies_id']        = $record['character_id'];
                $loginData['login_start_time'] = date('Y-m-d H:i:s');
                $loginData['login_status']     = Constant::LOGIN_STATUS_START;
                $loginData['working_status']   = $record['working_status'];
                $loginData['is_delete'] = false;
                 break;
            case Constant::LOGIN_STATUS_FINISH:
                //ログイン終了
                //recordはログイン中のLogintimeのデータ
                $loginData['id']             = $record['id'];
                break;
            default:
                break;
        }
        return $loginData;
    }

    /**
     * 一括でログイン記録を更新(新規・既存含む)
     * @param unknown $loginDataArr ログインのエンティティ
     */
    private function saveLoginEntity( $loginDataArr )
    {
        $insertDataArr   = [];
        $updateDataIdArr = [];

        foreach ( $loginDataArr as $loginData ){

            if( isset($loginData['id']) === true ){
                //更新処理
                $updateDataIdArr[] = $loginData['id'];
            } else {
                //新規データ
                $insertDataArr[] = $loginData;
            }
        }
        $this->bulkInsert( $insertDataArr);
        $this->bulkUpdate( $updateDataIdArr );
    }

    /**
     * ログイン記録を一括で入力
     *
     * @param unknown $insertDataArr 新規ログインデータ
     *
     */
    private function bulkInsert( $insertDataArr )
    {
        $logintimes       = TableRegistry::get('Logintimes');
        $logintimesEntities = $logintimes->newEntities( $insertDataArr );
        $logintimes ->saveMany( $logintimesEntities );
    }

    /**
     * ログイン記録を一括で終了
     *
     * @param unknown $updateDataIdArr loginテーブルのidの配列
     */
    private function bulkUpdate( $updateDataIdArr )
    {
        $this->query()
        ->update()
        ->set([
            'login_end_time' => date('Y-m-d H:i:s'),
            'login_status'   => Constant::LOGIN_STATUS_FINISH
        ])
        ->where(['id in' => $updateDataIdArr])
        ->execute();
    }
}
