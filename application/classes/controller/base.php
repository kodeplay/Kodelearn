<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Base extends Controller_Template {

    protected $content;

    public $template = 'template/template';

    protected $view;

    /**
     * Override the before method
     * check if ajax and select correct template
     */
    public function before()
    {
        $this->auth_filter();
        if (Auth::instance()->logged_in()) {
            $this->acl_filter();
        }
        // create a new Config reader and attach to the Config instance
        $config = Config::instance();
        $config->attach(new Config_Database());
        $this->template_filter();
        if ($this->request->is_ajax() || !$this->request->is_initial()) {
            $this->view = View::factory('template/content');
        } else {
            $this->view = View::factory($this->template);
            Breadcrumbs::add(array('Home', Url::site('home')));
        }
        return parent::before();
    }
	
//    public function action_index()
//    {
//        $this->response->body('hello, world!');
//    }

    /**
     * Check whether the user is logged in and set the correct 
     * template to handle both the cases
     * a logged in user cannot access auth page again
     * a non logged in user can only access the auth page (temporary)
     * @todo add other pages that non loggedin user can access.
     */
    protected function auth_filter() {
        $logged_in = Auth::instance()->logged_in();        
        $controller = $this->request->controller();
        $action = $this->request->action();
        if (!$logged_in && $controller !== 'auth') {
            $this->request->redirect('auth');
        }
        if ($logged_in && $controller === 'auth' && $action !== 'logout') {
            $this->request->redirect('home');
        }
    }    

    /**
     * Check if the role of the current user is allowed to access this page
     * otherwise redirect to the access denied page.
     * first we check if user has permission on whole using has_access method
     * then we check if acl for current resource action combination is defined and
     * check for it
     * lastly we resolve standard action names to valid resource-action combinations
     * and check for them
     */
    protected function acl_filter() {
        $resource = $this->request->controller();
        $acl = Acl::instance();
        if (!$acl->has_access($resource)) {
            $this->redirect_after_filter('error/access_denied');
            // Request::current()->redirect('error/access_denied');
        }
        // check if current acl for current controller-action is defined in permissions
        $action = $this->request->action();
        $repr_key = Acl::repr_key($resource, $action);
        if ($acl->acl_exists($repr_key) && !$acl->is_allowed($repr_key)) {
            $this->redirect_after_filter('error/access_denied');
            // Request::current()->redirect('error/access_denied');
        }
        // check for standard action names
        $std_actions = array(
            'index' => 'view',
            'add' => 'create',
            'edit' => 'edit',
            'delete' => 'delete',            
        );
        if (isset($std_actions[$action]) && !$acl->is_allowed(Acl::repr_key($resource, $std_actions[$action]))) {
            $this->redirect_after_filter('error/access_denied');
            // Request::current()->redirect('error/access_denied');
        }
        // if it reaches here, we assume the user has permission to this resource-level
        // any other checking will have to be done in the controller action
    }

    /**
     * Method to do the correct thing to deny access to the user to the requested 
     * resource as per the permissions. 
     * Depending upon the type of request, it will decide the mode of notifying the user
     * if action type = ajax - show overlay view
     * if action type = normal - redirect to the access denied page
     */
    protected function redirect_after_filter($page) {
        if ($this->request->is_ajax()) {
            echo json_encode(array('success' => 0, 'reason' => 'access_denied'));
            exit;
        } else {
            Request::current()->redirect($page);
        }
    }

    /**
     * Method to decide and set the template that will be used.
     * The decision will be taken depending upon whether the user is logged in 
     * or not
     */
    protected function template_filter() {
        $logged_in = Auth::instance()->logged_in();        
        $this->template = !$logged_in ? 'template/template' : 'template/logged_template';
    }

    public function after() {
        $controller = $this->request->controller();
        $action = $this->request->action();
        $page_description = Kohana::message('page_title', $controller.'_'.$action.'.description');
        $page_title = Kohana::message('page_title', $controller.'_'.$action.'.title');
        $breadcrumbs = Breadcrumbs::render();        
        $this->content = str_replace('replace_here_page_description', $page_description, $this->content);
        $this->content = str_replace('replace_here_page_title', $page_title, $this->content);
        if ($this->request->is_ajax() || !$this->request->is_initial()) {
            $this->response->body($this->content);
        } else {
            $title   = 'Kode Learn';
            $styles = array(
                'media/css/reset.css' => 'screen',
                'media/css/components.css' => 'screen',
                'media/css/kodelearn.css' => 'screen',
                'media/css/jquery-ui-1.8.14.custom.css' => 'screen'
            );
            $scripts = array(
                'media/javascript/jquery-1.6.2.min.js',
                'media/javascript/common.js',
                'media/javascript/classes.js',
                'media/javascript/events.js',
                'media/javascript/ajaxupload.js',
                'media/javascript/jquery-ui-1.8.14.custom.min.js',
                'media/javascript/jquery-ui-timepicker-addon.js',
                'media/javascript/kodelearnUI.js'
            );
            $this->view->set('content', $this->content);
            $this->view->set('styles', $styles);
            $this->view->set('scripts', $scripts);
            $this->view->set('title', $title . ' - ' . $page_title);
            $this->view->set('breadcrumbs', $breadcrumbs);            
            $this->menu_init();
            $this->response->body($this->view);
        }
    }

    protected function menu_init() {
        $this->view->bind('topmenu', $topmenu)
            ->bind('sidemenu', $sidemenu)
            ->bind('myaccount', $myaccount)
            ->bind('image', $image)
            ->bind('role', $role)
            ->bind('username', $username)
            ->bind('user', $user);
        if (!Auth::instance()->logged_in()) {
            $role = 'guest';
        } else {
            $user = Auth::instance()->get_user();
            $role = $user->role()->name;
            $username = Auth::instance()->get_user()->firstname;
            if ($user->is_role('student')) {
                $avatar = Auth::instance()->get_user()->avatar;
                $avatar = $avatar === null ? '' : $avatar;
                $this->view->set('avatar', CacheImage::instance()->resize($avatar, 72, 72));
            }
        }
        $menu = Acl_Menu::factory($role);
        // var_dump($menu); exit;
        $topmenu = $menu->get('topmenu');
        $sidemenu = $menu->get('sidemenu');
        $myaccount = $menu->get('myaccount');
        $institution = ORM::factory('institution', $id=1);
        $image = CacheImage::instance()->resize($institution->logo, 240, 60);
    }
}
