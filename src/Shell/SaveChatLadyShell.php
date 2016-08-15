<?php
namespace App\Shell;

use Cake\Console\Shell;

class SaveChatLadyShell extends Shell
{
    public function main()
    {
        parent::initialize();
        $this->out('start task');
        $this->loadModel('Ladies');
        $this->Ladies->action();
        $this->out('end task');
    }

}