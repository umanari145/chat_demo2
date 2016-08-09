<?php
namespace App\Controller;

use App\Controller\AppController;

/**
 * Ladies Controller
 *
 * @property \App\Model\Table\LadiesTable $Ladies
 */
class LadiesController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $query  = $this->getQuery( $this->request->query );
        $ladies = $this->paginate($query);

        $this->set(compact('ladies'));
        $this->set('_serialize', ['ladies']);
    }

    /**
     * View method
     *
     * @param string|null $id Lady id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $lady = $this->Ladies->get($id, [
            'contain' => []
        ]);

        $this->set('lady', $lady);
        $this->set('_serialize', ['lady']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $lady = $this->Ladies->newEntity();
        if ($this->request->is('post')) {
            $lady = $this->Ladies->patchEntity($lady, $this->request->data);
            if ($this->Ladies->save($lady)) {
                $this->Flash->success(__('The lady has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The lady could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('lady'));
        $this->set('_serialize', ['lady']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Lady id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $lady = $this->Ladies->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $lady = $this->Ladies->patchEntity($lady, $this->request->data);
            if ($this->Ladies->save($lady)) {
                $this->Flash->success(__('The lady has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The lady could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('lady'));
        $this->set('_serialize', ['lady']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Lady id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $lady = $this->Ladies->get($id);
        if ($this->Ladies->delete($lady)) {
            $this->Flash->success(__('The lady has been deleted.'));
        } else {
            $this->Flash->error(__('The lady could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * クエリを取得
     * @param unknown $query クエリオブジェクト
     * @return query クエリオブジェクト
     */
    private function getQuery( $query )
    {
        $searchWord = ( !empty($query['keyword']) ) ? $query['keyword']: "";
        $conditions;
        if( !empty($searchWord) ) {

            $conditions = [ 'conditions' => [
                 'Ladies.name LIKE' => '%' . $searchWord .'%']
            ];

        }

        if( !empty($conditions) ){
             $query = $this->Ladies->find('all' , $conditions);
        } else {
             $query = $this->Ladies->find('all');
        }

        return $query;
    }

}
