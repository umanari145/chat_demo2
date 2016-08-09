<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;
use Cake\Collection\Collection;
use App\Util\Constant;

require_once ROOT .'/src/Util/DOMEvent.php';

/**
 * Ladies Model
 *
 * @method \App\Model\Entity\Lady get($primaryKey, $options = [])
 * @method \App\Model\Entity\Lady newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Lady[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Lady|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Lady patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Lady[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Lady findOrCreate($search, callable $callback = null)
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class LadiesTable extends Table
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

        $this->table('ladies');
        $this->displayField('name');
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
            ->requirePresence('code', 'create')
            ->notEmpty('code');

        $validator
            ->allowEmpty('name');

        $validator
            ->allowEmpty('image_url');

        $validator
            ->allowEmpty('url');

        $validator
            ->allowEmpty('prof');

        $validator
            ->boolean('is_delete')
            ->requirePresence('is_delete', 'create')
            ->notEmpty('is_delete');

        return $validator;
    }

    /**
     * スクレイピングの起動
     *
     * 1 ladyId => 'ステータス状態'のハッシュを取得
     * 2 ladyIdが存在しているか否かの確認し、存在していない場合、登録
     */
    public function action()
    {
        $totalLadyHash = $this->getParsedHTMLContents(Constant::DMM_URL);
        $totalLadyIdList = array_keys( $totalLadyHash );
        $this->isExistLadyAndRegist( $totalLadyIdList );

    }

    /**
     * メインのコントローラー(ここで処理を行う)
     *
     * @param スクレイピング対象のURL
     * @return 女性のidのハッシュ
     */
    private function getParsedHTMLContents( $url = "" )
    {

        if ( empty ( $url )) return false;

        $html = file_get_contents ( $url );

        if (! empty ( $html )) {
           $dom = \phpQuery::newDocument($html);
            //女子データを稼働状況ごとに取得する
            $workingStatueArr =['waiting','party','twoshot'];
            $totalLadyHash;
            foreach ( $workingStatueArr as $workingStatus ) {
                $selector     = 'anchor_' . $workingStatus;
                $waitingList  = $dom["#".$selector]->find("ul li.listbox");
                $ladiesIdList = $this->getLadyIdList( $waitingList );
                $totalLadyHash[$workingStatus] = $ladiesIdList;
            }
            //最終的にid=>working_statusの状態にする
            $ladyIdHashFinal = $this->convertGrilIdData( $totalLadyHash );
            return $ladyIdHashFinal;

        } else {
            return false;
        }

    }

    /**
     * チャットレディレディがすでに登録されているかの確認
     * 登録されていなければ登録する
     *
     * @param unknown $totalLadyIdHash
     */
    private function isExistLadyAndRegist( $totalLadyIdList )
    {
        $chatladyIdHash = $this->getRegistredChatLadyId();
        $chatladyHashArr;
        $count = 0;
        foreach ( $totalLadyIdList as $ladyId ) {
            if( isset( $chatladyIdHash[$ladyId]) !== true ) {
                //idの登録なし
                $chatladyHashArr[] =$this->getChatLadyHashArr( $ladyId );
                $count++;

                //if( $count === 10 ) break;
            }
        }
        $this->saveChatLadyEntity( $chatladyHashArr);

    }

    /**
     * チャットレディの登録
     *
     * @param unknown $ladyId チャットレディID
     */
    private function getChatLadyHashArr( $ladyId )
    {
        $detailUrl = $this->getChatLadyDetailPageUrl( $ladyId );
        $html      = file_get_contents ( $detailUrl );

        if (! empty ( $html )) {
            $dom = \phpQuery::newDocument($html);
            return $this->getLadiesProperty( $dom, $ladyId );
        }
    }

    /**
     * 必要な情報の取得
     *
     * @param unknown $dom domデータ
     * @param string $ladyId チャットレディID
     */
    private function getLadiesProperty( $dom, $ladyId )
    {
        $name     =  $dom['div.l-box p.name']->text();
        $imageUrl = ( !empty( $ladyId  )) ? $this->getLadyImageURL( $ladyId ) : "";
        $profile  = $dom['p.data-comment']->html();

        $data =[
                'code'      => $ladyId,
                'name'      => $name,
                'image_url' => $imageUrl,
                'url'       => $this->getChatLadyDetailPageUrl( $ladyId ),
                'profile'   => $profile
        ];

        Log::write('debug', 'code ' . $ladyId , ' ladyname ' . $name );

        return $data;
    }


    /**
     * データベースにエンティティを登録する
     *
     * @param unknown $data
     */
    private function saveChatLadyEntity( $records )
    {
        foreach ( $records as $key => &$record )
        {
            if( empty($record['code']) || empty( $record['name']) )
            {
                unset($records[$key]);
            }

            $record['is_delete'] = false;
        }

        $ladies       = TableRegistry::get('Ladies');
        $ladyEntities = $ladies->newEntities( $records );
        $ladies->saveMany( $ladyEntities );
    }


    /**
     * チャットレディの詳細ページURLの取得
     *
     * @param unknown $girId チャットレディID
     */
    private function getChatLadyDetailPageUrl( $girId )
    {
        return Constant::DMM_URL ."-/chat-room/=/character_id=". $girId . "/";
    }


    /**
     * チャットレディの画像URLを取得
     *
     * @param unknown $ladyId チャットレディID
     */
    private function getLadyImageURL( $ladyId )
    {
        return Constant::CHAT_GIRL_IMG_URL . sprintf('%08d', $ladyId ) ."/profile_l.jpg";
    }



    /**
     * チャットレディのidのハッシュを作る
     * 登録されていなければ登録する
     *
     * @return チャットレディのidのハッシュ
     *
     **/
    private function getRegistredChatLadyId()
    {
        $ladies = TableRegistry::get('Ladies');

        $codeHashArr = $ladies->find()
                        ->select(['code'])
                        ->hydrate(false)
                        ->toList();

        $chatladyIdHash = [];
        foreach ( $codeHashArr as $codeHash )
        {
            $chatladyIdHash[$codeHash['code']] = 1;
        }
        return $chatladyIdHash;
    }

    /**
     * 女性リストの取得
     *
     * @param unknown $ladiesList 女性のデータが入ったDOMデータ
     * @return character_idを格納した女性のリスト
     */
    private function getLadyIdList( $ladiesList =[] ) {
        $ladiesListafterExtract=[];

        foreach ( $ladiesList as $ladyEle) {
            $href = pq($ladyEle)->find('a')->attr('href');
            if( empty($href)) continue;

            preg_match_all( '/^.*id=(\d+)\/$/', $href ,$res);

            if( empty($res[1][0]) ) continue;

            $ladiesListafterExtract[] = $res[1][0];
        }
        return $ladiesListafterExtract;
    }

    /**
     * workingstatus=>idListから id =>workingstatusの状態に変更をする
     *
     * @param unknown $totalLadyHash workingstatus=>idList
     * @return id =>workingstatusのハッシュ
     */
    private function convertGrilIdData( $totalLadyHash =[]) {
        $ladyIdHashFinal;
        foreach( $totalLadyHash as $workingStatus => $ladyList ) {
            foreach ( $ladyList as $ladyId){
                $ladyIdHashFinal[$ladyId] = $workingStatus;
            }
        }
        return $ladyIdHashFinal;
    }

    /**
     * ログイン状態と非ログイン状態のスタッフを分ける
     *
     * @param unknown $ladyIdHashFinal ログインユーザーのcharacter_id
     * @param unknown $allCharacterIdList character_idのデータ
     * @return ログイン/非ログインごとのユーザーのデータ
     */
    private function getLoginStaffUserList( $ladyIdHashFinal, $allCharacterIdList ) {

        $UserList =[
                'login'    => [],
                'not_login'    =>[]
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
                $working_status_num = 1;
                break;
            case 'party':
                $working_status_num = 2;
                break;
            case 'twoshot':
                $working_status_num = 3;
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
    private function registLoginStaffData( $userList =[]){

        foreach ( $userList['login'] as $userData ) {
            $this->divLoginStatus( $userData ,true );
        }

        foreach ( $userList['not_login'] as $userData2 ) {
            $this->divLoginStatus( $userData2 ,false );
        }
        $sqlLog = $this->getDataSource()->getLog(false, false);
        debug($sqlLog , false);

    }

    /**
     * ログインステータスの判定とデータの更新
     *
     * @param unknown $userData ユーザーデータ(user_idの入ったデータ)
     * @param string $isLogin true(ログイン中)/false(ログインしていない)
     */
    private function divLoginStatus($userData = [], $isLogin = true) {

        if ($isLogin === true) {
            //ログインユーザー
            //今現在ログインをしていて
            $hasLoginData = $this->hasLogin( $userData['character_id']);

            if( $hasLoginData !== false ) {
                //ステータス変更あり
                if( $hasLoginData['working_status'] != $userData['working_status'] ) {
                    //以前のステータス情報を閉じる
                    $this->updateUserLoginStatus( $hasLoginData, "2");
                    //新規の記録の場合は何もしない
                    $this->updateUserLoginStatus( $userData, "1");
                }
                //ステータス変更ない場合は何もしない

            } else {
                //ログイン記録がない場合は新規の記録
                $this->updateUserLoginStatus( $userData, "1");
            }

        } else {
            //非ログインユーザー
            //今現在ログインをしていなくて前回処理時にログインがある→ログイン終了をする
            $hasLoginData = $this->hasLogin( $userData['character_id']);
            if( $hasLoginData !== false ) {
                $this->updateUserLoginStatus( $hasLoginData, "2");
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
        $hasLoginData = $this->find ( 'first', [
                'conditions' => [
                        'character_id' => $character_id,
                        'login_status' => 1
                ]
        ] );
        return ( count($hasLoginData) > 0 ) ? $hasLoginData["Logintime"] : false;
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

        switch( $status ){
            case '1':
                //新規ログイン記録
                //userDataはUserから取得したデータ
                $data['character_id'] = $userData['character_id'];
                $data['login_start_time'] = date('Y-m-d H:i:s');
                $data['login_status'] = 1;
                $data['working_status'] = $userData['working_status'];
                break;
            case '2':
                //ログイン終了
                //userDataはログイン中のLogintimeのデータ
                $data['id'] = $userData['id'];
                $data['login_end_time'] = date('Y-m-d H:i:s');
                $data['login_status'] = 2;
                break;
            default:
                break;
        }

        $this->create();
        $this->save( $data);
        return true;
    }

    /**
     * ある対象期間(yyyy/MM)のログイン時間を取得する
     *
     * @param string $targetId 対象者id
     * @param string $targetMonthVal 対象期間(yyyy/MM)
     * @return int ログイン時間(秒)
     */
    private function getLoginSumTimeByTargetMonth( $targetId="" ,$targetMonthVal="") {

        if( empty( $targetId) ) return false;

        $dateUtility = new DateUtility();
        list( $startTime, $endTime ) = $dateUtility->getMonthStartAndEnd( $targetMonth );

        $conditions =[
                'fields' =>[
                        'SUM(UNIX_TIMESTAMP(Logintime.login_end_time)-UNIX_TIMESTAMP(Logintime.login_start_time)) as login_sum_time',
                ],
                'conditions' =>[
                        'Logintime.character_id' => $targetId,
                        'Logintime.login_status' => 2,
                        'Logintime.login_end_time >= ' => $startTime,
                        'Logintime.login_end_time <= ' => $endTime
                ],
        ];

        $loginData = $this->find('first', $conditions );

        if (!empty( $loginData[0]['login_sum_time'] ) ) {
            return $loginData[0]['login_sum_time'];
        } else {
            return false;
        }
    }
}
