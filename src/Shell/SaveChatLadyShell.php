<?php
namespace App\Shell;

use Cake\Console\Shell;
use App\Util\Constant;
use Cake\Collection\Collection;

class SaveChatLadyShell extends Shell
{


    /**
     * スクレイピングの起動
     *
     * 1 ladyId => 'ステータス状態'のハッシュを取得
     * 2 ladyIdが存在しているか否かの確認し、存在していない場合、登録
     * 3 ログイン時間を記録
     */
    public function main()
    {
        parent::initialize();
        $this->out('start task');
        $this->loadModel('Ladies');
        $this->loadModel('Logintimes');

        $totalLadyHash = $this->Ladies->getParsedHTMLContents(Constant::DMM_URL);

        //$totalLadyIdList = array_keys( $totalLadyHash );

        //$this->isExistLadyAndRegist( $totalLadyIdList );

        $chatLadyIdList  = $this->Ladies->find('all')->select(['code'])->where(['is_delete' => false ])->hydrate(false)->toList();
        $collection      = new Collection($chatLadyIdList);
        $chatLadyIdList  = $collection->extract('code')->toArray();

        $userList       = $this->Logintimes->getLoginUserList( $totalLadyHash, $chatLadyIdList);

        $this->Logintimes->registLoginStaffData( $userList );

        $this->out('end task');
    }

}