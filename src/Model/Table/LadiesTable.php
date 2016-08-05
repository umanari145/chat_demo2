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
            ->boolean('is_delete')
            ->requirePresence('is_delete', 'create')
            ->notEmpty('is_delete');

        return $validator;
    }

    /**
     * スクレイピングの起動
     *
     * 1 girlId => 'ステータス状態'のハッシュを取得
     * 2 girlIdが存在しているか否かの確認し、存在していない場合、登録
     */
    public function action()
    {
        $totalGirlHash = $this->getParsedHTMLContents(Constant::DMM_URL);

        $totalGirlIdList = array_keys( $totalGirlHash );

        $this->isExistGirlAndRegist( $totalGirlIdList );

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

            $dom = \DomDocument::loadHTML ( $html );
            $xml = simplexml_import_dom ( $dom );
            //女子データを稼働状況ごとに取得する
            $workingStatueArr =['waiting','party','twoshot'];
            $totalGirlHash;
            foreach ( $workingStatueArr as $workingStatus ) {
                $selector = 'anchor_' . $workingStatus;
                $waitingList = $xml->xpath ( '//div[@id="' . $selector . '"]/ul/li' );
                $girlsListafterExtract = $this->getGirlIdList( $waitingList );

                $collection = new Collection($girlsListafterExtract);
                $girlsList = $collection->extract('id')->toArray();
                $totalGirlHash[$workingStatus] = $girlsList;
            }
            //最終的にid=>working_statusの状態にする
            $girlIdHashFinal = $this->convertGrilIdData( $totalGirlHash );
            return $girlIdHashFinal;

        } else {
            return false;
        }

    }

    /**
     * チャットガールガールがすでに登録されているかの確認
     * 登録されていなければ登録する
     *
     * @param unknown $totalGirlIdHash
     */
    private function isExistGirlAndRegist( $totalGirlIdList )
    {
        $chatgirlIdHash = $this->getRegistredChatGirlId();
        $chatgirlHashArr;
        $count = 0;
        foreach ( $totalGirlIdList as $girlId ) {
            if( isset( $chatgirlIdHash[$girlId]) !== true ) {
                //idの登録なし
                $chatgirlHashArr[] =$this->getChatGirlHashArr( $girlId );
                $count++;

                //if( $count === 10 ) break;
            }
        }
        $this->saveChatLadyEntity( $chatgirlHashArr);

    }

    /**
     * チャットレディの登録
     *
     * @param unknown $girlId チャットレディID
     */
    private function getChatGirlHashArr( $girlId )
    {

        $detailUrl = $this->getChatGirlDetailPageUrl( $girlId );
        $html      = file_get_contents ( $detailUrl );

        if (! empty ( $html )) {
            $dom  = \DomDocument::loadHTML ( $html );
            $xml  = simplexml_import_dom ( $dom );
            return $this->getGirlsProperty( $xml, $girlId );
        }
    }

    /**
     * 必要な情報の取得
     *
     * @param unknown $xml xmlデータ
     * @param string $girlId チャットガールID
     */
    private function getGirlsProperty( $xml, $girlId )
    {

        $nameEle = $xml->xpath ( '//div[contains(@class,"char-name")]/p[@class="name"]' );
        $girlName =( !empty( $this->getPropertyFromElement( $nameEle ))) ? $this->getPropertyFromElement( $nameEle ) :"";
        $imageUrl =( !empty( $this->getGirlImageURL( $girlId ))) ? $this->getGirlImageURL( $girlId ) : "";

        $data =[
                'code'      => $girlId,
                'name'      => $girlName,
                'image_url' => $imageUrl,
                'url'       => $this->getChatGirlDetailPageUrl( $girlId )
        ];

        Log::write('debug', 'code ' . $girlId , ' ladyname ' . $girlName );

        return $data;
    }

    /**
     * データベースにエンティティを登録する
     *
     * @param unknown $data
     */
    private function saveChatLadyEntity( $records )
    {
        foreach ( $records as &$record )
        {
            $record['is_delete'] = false;
        }

        $ladies       = TableRegistry::get('Ladies');
        $ladyEntities = $ladies->newEntities( $records );
        $ladies->saveMany( $ladyEntities );
    }


    /**
     * チャットガールの詳細ページURLの取得
     *
     * @param unknown $girId チャットガールID
     */
    private function getChatGirlDetailPageUrl( $girId )
    {
        return Constant::DMM_URL ."-/chat-room/=/character_id=". $girId . "/";
    }


    /**
     * チャットガールの画像URLを取得
     *
     * @param unknown $girlId チャットガールID
     */
    private function getGirlImageURL( $girlId )
    {
        return Constant::CHAT_GIRL_IMG_URL . sprintf('%08d', $girlId ) ."/profile_l.jpg";
    }



    /**
     * チャットレディのidのハッシュを作る
     * 登録されていなければ登録する
     *
     * @return チャットガールのidのハッシュ
     *
     **/
    private function getRegistredChatGirlId()
    {
        $ladies = TableRegistry::get('Ladies');

        $codeHashArr = $ladies->find()
                        ->select(['code'])
                        ->hydrate(false)
                        ->toList();

        $chatgirlIdHash = [];
        foreach ( $codeHashArr as $codeHash )
        {
            $chatgirlIdHash[$codeHash['code']] = 1;
        }
        return $chatgirlIdHash;
    }

    /**
     * 女性リストの取得
     *
     * @param unknown $girlsList 女性のデータが入ったDOMデータ
     * @return multitype:id/classを格納した女性のリスト
     */
    private function getGirlIdList( $girlsList =[] ) {
        $girlsListafterExtract=[];

        foreach ( $girlsList as $girlEle) {

            $idElement = $girlEle->attributes();
            $girlData =  $this->getPropertyFromElementFromAllGirlList( $idElement );
            if( $girlData !== false) {
                $girlsListafterExtract[] = $girlData;
            }
        }
        return $girlsListafterExtract;
    }

    /**
     * XML要素からプロパティを取得する
     *
     * @param $idElement DOM要素
     * @return id/classを格納したクラス / false(取得失敗)
     */
    private function getPropertyFromElement( $idElement = [] ) {

        foreach ( $idElement as $attr => $property ) {
            if( !empty( $property ) ) {
                return $property;
            }
        }
        return false;
    }

    /**
     * XML要素からプロパティを取得する
     *
     * @param $idElement DOM要素
     * @return id/classを格納したクラス / false(取得失敗)
     */
    private function getPropertyFromElementFromAllGirlList( $idElement =[]) {

        $girlData =[];
        foreach ( $idElement as $attr => $property ) {
            if( empty( $property )) next;

            switch( $attr ) {
                case 'id':
                    $tmp = explode("_", $property );
                    if( preg_match('/^\d*$/', $tmp[1]) === 1 ){
                        $id = $tmp[1];
                    }
                    if ( !empty( $id)) $girlData['id'] = $id;
                    break;
            }
        }

        if( !empty( $girlData['id'])) {
            return $girlData;
        } else {
            return false;
        }
    }

    /**
     * workingstatus=>idListから id =>workingstatusの状態に変更をする
     *
     * @param unknown $totalGirlHash workingstatus=>idList
     * @return id =>workingstatusのハッシュ
     */
    private function convertGrilIdData( $totalGirlHash =[]) {
        $girlIdHashFinal;
        foreach( $totalGirlHash as $workingStatus => $girlList ) {
            foreach ( $girlList as $girlId){
                $girlIdHashFinal[$girlId] = $workingStatus;
            }
        }
        return $girlIdHashFinal;
    }

    /**
     * ログイン状態と非ログイン状態のスタッフを分ける
     *
     * @param unknown $girlIdHashFinal ログインユーザーのcharacter_id
     * @param unknown $allCharacterIdList character_idのデータ
     * @return ログイン/非ログインごとのユーザーのデータ
     */
    private function getLoginStaffUserList( $girlIdHashFinal, $allCharacterIdList ) {

        $UserList =[
                'login'    => [],
                'not_login'    =>[]
        ];

        foreach ( $allCharacterIdList as $characterId ) {

            if( isset($girlIdHashFinal[$characterId]) === true ) {
                #ログインしているユーザー
                $UserList['login'][] = [
                'character_id'   => $characterId,
                'working_status' => $this->getWorkingStatusNum( $girlIdHashFinal[$characterId])
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
