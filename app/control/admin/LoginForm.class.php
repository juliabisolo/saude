<?php
/**
 * LoginForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class LoginForm extends TPage
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        $this->style = 'clear:both';
        // creates the form
        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle( 'LOGIN' );
        
        // create the form fields
        $login = new TEntry('login');
        $password = new TPassword('password');
        
        // define the sizes
        $login->setSize('70%', 40);
        $password->setSize('70%', 40);

        $login->style = 'height:35px; font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        $password->style = 'height:35px;font-size:14px;float:left;border-bottom-left-radius: 0;border-top-left-radius: 0;';
        
        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');
        
        $login->autofocus = 'autofocus';

        $user   = '<span class="login-avatar"><span class="fa fa-user"></span></span>';
        $locker = '<span class="login-avatar"><span class="fa fa-lock"></span></span>';
        
        $this->form->addFields( [$user, $login] );
        $this->form->addFields( [$locker, $password] );
        
        $btn = $this->form->addAction(_t('Log in'), new TAction(array($this, 'onLogin')), '');
        $btn->class = 'btn btn-primary';
        $btn->style = 'height: 40px;width: 90%;display: block;margin: auto;font-size:17px;';
        
        $wrapper = new TElement('div');
        $wrapper->style = 'margin:auto; margin-top:100px;max-width:460px;';
        $wrapper->id    = 'login-wrapper';
        $wrapper->add($this->form);
        
        // add the form to the page
        parent::add($wrapper);
    }
    
    /**
     * user exit action
     * Populate unit combo
     */
    public static function onExitUser($param)
    {
        try
        {
            TTransaction::open('permission');
            
            $user = SystemUser::newFromLogin( $param['login'] );
            if ($user instanceof SystemUser)
            {
                $units = $user->getSystemUserUnits();
                $options = [];
                
                if ($units)
                {
                    foreach ($units as $unit)
                    {
                        $options[$unit->id] = $unit->name;
                    }
                }
                TCombo::reload('form_login', 'unit_id', $options);
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Authenticate the User
     */
    public static function onLogin($param)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        try
        {
            $data = (object) $param;
            
            (new TRequiredValidator)->validate( _t('Login'),    $data->login);
            (new TRequiredValidator)->validate( _t('Password'), $data->password);
            
            
            TSession::regenerate();
            $user = ApplicationAuthenticationService::authenticate( $data->login, $data->password );
            
            if ($user)
            {
                SystemAccessLogService::registerLogin();
                
                $frontpage = $user->frontpage;
                if ($frontpage instanceof SystemProgram and $frontpage->controller)
                {
                    AdiantiCoreApplication::gotoPage($frontpage->controller); // reload
                    TSession::setValue('frontpage', $frontpage->controller);
                }
                else
                {
                    AdiantiCoreApplication::gotoPage('EmptyPage'); // reload
                    TSession::setValue('frontpage', 'EmptyPage');
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error',$e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /** 
     * Reload permissions
     */
    public static function reloadPermissions()
    {
        try
        {
            TTransaction::open('permission');
            $user = SystemUser::newFromLogin( TSession::getValue('login') );
            
            if ($user)
            {
                $programs = $user->getPrograms();
                $programs['LoginForm'] = TRUE;
                TSession::setValue('programs', $programs);
                
                $frontpage = $user->frontpage;
                if ($frontpage instanceof SystemProgram AND $frontpage->controller)
                {
                    TApplication::gotoPage($frontpage->controller); // reload
                }
                else
                {
                    TApplication::gotoPage('EmptyPage'); // reload
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     *
     */
    public function onLoad($param)
    {
    }
    
    /**
     * Logout
     */
    public static function onLogout()
    {
        SystemAccessLogService::registerLogout();
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginForm', '');
    }
}