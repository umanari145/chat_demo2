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
     * メインのコントローラー(ここで処理を行う)
     *
     * @param スクレイピング対象のURL
     * @return 女性のidのハッシュ
     */
    public function getParsedHTMLContents( $url = "" )
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
    public function isExistLadyAndRegist( $totalLadyIdList )
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
    public function getRegistredChatLadyId()
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

}
