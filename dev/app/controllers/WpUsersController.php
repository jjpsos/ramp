<?php
declare(strict_types=1);


use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model;
//use Phalcon\Db\Adapter\Pdo\Mysql;


class WpUsersController extends ControllerBase
{
    /**
     * Index action
     */
    public function indexAction()
    {
        $this->view->setVar('wp_user', new WpUsers());
    }

    /**
     * Searches for wp_users
     */
    public function searchAction()
    {
        $numberPage = $this->request->getQuery('page', 'int', 1);
        $parameters = Criteria::fromInput($this->di, 'WpUsers', $_GET)->getParams();
        $parameters['order'] = "ID";

        $paginator   = new Model(
            [
                'model'      => 'WpUsers',
                'parameters' => $parameters,
                'limit'      => 3,
                'page'       => $numberPage,
            ]
        );

        $paginate = $paginator->paginate();

        if (0 === $paginate->getTotalItems()) {
            $this->flash->notice("The search did not find any wp_users");

            $this->dispatcher->forward([
                "controller" => "wp_users",
                "action" => "index"
            ]);

            return;
        }

        $this->view->page = $paginate;
    }

    /**
     * Displays the creation form
     */
    public function newAction()
    {
        $this->view->setVar('wp_user', new WpUsers());
    }

    /**
     * Edits a wp_user
     *
     * @param string $ID
     */
    public function editAction($ID)
    {

        if (!$this->request->isPost()) {
            $wp_user = WpUsers::findFirstByID($ID);
            if (!$wp_user) {
                $this->flash->error("wp_user was not found");

                $this->dispatcher->forward([
                    'controller' => "wp_users",
                    'action' => 'index'
                ]);

                return;
            }

            $this->view->ID = $wp_user->ID;
            $this->view->setVar('wp_user', $wp_user);
            
            //$assignTagDefaults$
        }
    }

    /**
     * Creates a new wp_user
     */
    public function createAction()
    {
        if (!$this->request->isPost()) {
            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'index'
            ]);

            return;
        }

        $wp_user = new WpUsers();
        $wp_user->userLogin = $this->request->getPost("user_login");
        $wp_user->userPass = $this->request->getPost("user_pass");
        //$wp_user->userNicename = $this->request->getPost("user_nicename");
        $wp_user->userNicename = "Nicename";
        $wp_user->userEmail = $this->request->getPost("user_email");
        $wp_user->userUrl = $this->request->getPost("user_url");
        $wp_user->userRegistered = $this->request->getPost("user_registered");
        $wp_user->userActivationKey = $this->request->getPost("user_activation_key");
        $wp_user->userStatus = $this->request->getPost("user_status", "int");
        $wp_user->displayName = $this->request->getPost("display_name");

        $wp_user->assign(
            [
                'user_login' => $this->request->getPost("user_login"),
                'user_pass'  => $this->request->getPost("user_pass")
            ]
        );     
        
        if (!$wp_user->save()) {
            foreach ($wp_user->getMessages() as $message) {
                $this->flash->error($message);
            }

            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'new'
            ]);

            return;
        }

        $this->flash->success("wp_user was created successfully!!!");

        $this->dispatcher->forward([
            'controller' => "wp_users",
            'action' => 'index'
        ]);
    }

    /**
     * Saves a wp_user edited
     *
     */
    public function saveAction()
    {

        if (!$this->request->isPost()) {
            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'index'
            ]);

            return;
        }

        $ID = $this->request->getPost("ID");
                
        $wp_user = WpUsers::findFirstByID($ID);

        if (!$wp_user) {
            $this->flash->error("wp_user does not exist " . $ID);

            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'index'
            ]);

            return;
        }

        $wp_user->userLogin = $this->request->getPost("user_login");
        $wp_user->userPass = $this->request->getPost("user_pass");
        $wp_user->userNicename = $this->request->getPost("user_nicename");
        $wp_user->userEmail = $this->request->getPost("user_email");
        $wp_user->userUrl = $this->request->getPost("user_url");
        $wp_user->userRegistered = $this->request->getPost("user_registered");
        $wp_user->userActivationKey = $this->request->getPost("user_activation_key");
        $wp_user->userStatus = $this->request->getPost("user_status", "int");
        $wp_user->displayName = $this->request->getPost("display_name");

        $wp_user->assign(
            [
                'user_login' => $this->request->getPost("user_login"),
                'user_pass'  => $this->request->getPost("user_pass")
            ]
        );
        

        if (!$wp_user->save()) {

            foreach ($wp_user->getMessages() as $message) {
                $this->flash->error($message);
            }

            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'edit',
                'params' => [$wp_user->ID]
            ]);

            return;
        }

        $this->flash->success("wp_user was updated successfully");

        $this->dispatcher->forward([
            'controller' => "wp_users",
            'action' => 'index'
        ]);
    }

    /**
     * Deletes a wp_user
     *
     * @param string $ID
     */
    public function deleteAction($ID)
    {
        $wp_user = WpUsers::findFirstByID($ID);
        
        if (!$wp_user) {
            $this->flash->error("wp_user was not found");

            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'index'
            ]);

            return;
        }

        if (!$wp_user->delete()) {

            foreach ($wp_user->getMessages() as $message) {
                $this->flash->error($message);
            }

            $this->dispatcher->forward([
                'controller' => "wp_users",
                'action' => 'search'
            ]);

            return;
        }

        $this->flash->success("wp_user was deleted successfully");

        $this->dispatcher->forward([
            'controller' => "wp_users",
            'action' => "index"
        ]);
    }
}
