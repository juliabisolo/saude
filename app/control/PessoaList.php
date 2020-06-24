<?php
/**
 * PessoaList Listing
 */
class PessoaList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_Pessoa');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Pessoas');

        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');

        // add the fields
        $this->form->addQuickField('Id', $id,  200);
        $this->form->addQuickField('Nome', $nome,  200);
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Pessoa_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('PessoaForm', 'onEdit')), 'fa:plus-circle green');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(400);  

        // creates the datagrid columns
        $column_id            = new TDataGridColumn('id',            'Id',                 'left');
        $column_nome          = new TDataGridColumn('nome',          'Nome',               'left');
        $column_telefone      = new TDataGridColumn('telefone',      'Telefone',           'left');
        $column_endereco      = new TDataGridColumn('endereco',      'Endereço',           'left');
        $column_cpf           = new TDataGridColumn('cpf',           'CPF',                'left');
        $column_dt_nascimento = new TDataGridColumn('dt_nascimento', 'Data de nascimento', 'left');
        $column_fl_ativo      = new TDataGridColumn('fl_ativo',      'Ativo',              'left');

        $column_fl_ativo->setTransformer(function($fl_ativo)
        {
            $icone = new TElement('i');
            
            $title = 'Inativo';
            $class = "ban";
            $icone->style = "padding-right:4px; color:red";

            if($fl_ativo)
            {
                $title = 'Ativo';    
                $class = "check";
                $icone->style = "padding-right:4px; color:green";
            }

            $icone->title = $title;
            $icone->class = "fa fa-{$class} fa-fw";

            return $icone;
        });

        $column_dt_nascimento->setTransformer(array($this, 'formatDate'));

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_endereco);
        $this->datagrid->addColumn($column_cpf);
        $this->datagrid->addColumn($column_dt_nascimento);
        $this->datagrid->addColumn($column_fl_ativo);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('PessoaForm', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:edit blue');
        $action_edit->setField('id');  

        //create DESATIVAR action
        $action_desativar = new TDataGridAction(array($this, 'onDesativar'));
        $action_desativar->setUseButton(TRUE);
        $action_desativar->setButtonClass('btn btn-default');
        $action_desativar->setLabel(('Desativar'));
        $action_desativar->setImage('fa:user-times red');
        $action_desativar->setField('id');
        $action_desativar->setDisplayCondition( array($this, 'displayDesativa') );

        //create ATIVAR action
        $action_ativar = new TDataGridAction(array($this, 'onAtivar'));
        $action_ativar->setUseButton(TRUE);
        $action_ativar->setButtonClass('btn btn-default');
        $action_ativar->setLabel(('Ativar'));
        $action_ativar->setImage('fa:user green');
        $action_ativar->setField('id');
        $action_ativar->setDisplayCondition( array($this, 'displayAtiva') );

        $action_group = new TDataGridActionGroup('Ações', 'bs:th');        
        $action_group->addAction($action_edit);
        $action_group->addAction($action_desativar);
        $action_group->addAction($action_ativar);
        
        $this->datagrid->addActionGroup($action_group);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($this->datagrid);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }

    //formata data BR
    public function formatDate($date, $object)
    {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    }
    
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('PessoaList_filter_id',   NULL);
        TSession::setValue('PessoaList_filter_nome',   NULL);

        if (isset($data->id) AND ($data->id)) {
            $filter = new TFilter('id', '=', "$data->id"); // create the filter
            TSession::setValue('PessoaList_filter_id',   $filter); // stores the filter in the session
        }


        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%"); // create the filter
            TSession::setValue('PessoaList_filter_nome',   $filter); // stores the filter in the session
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('Pessoa_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'agenda_julia'
            TTransaction::open('planosaude');
            
            // creates a repository for Pessoa
            $repository = new TRepository('Pessoa');
            
            $limit = 10;

            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            
            if (TSession::getValue('PessoaList_filter_id')) {
                $criteria->add(TSession::getValue('PessoaList_filter_id')); // add the session filter
            }

            if (TSession::getValue('PessoaList_filter_nome')) {
                $criteria->add(TSession::getValue('PessoaList_filter_nome')); // add the session filter
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

      public function onDesativar($param)
    {
        $action = new TAction(array(__CLASS__, 'desativa'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Você tem certeza que deseja desativar este usuário?', $action);
    }

    public function desativa($param)
    {
        TTransaction::open('planosaude');
        $pessoa = new Pessoa($param['id']);
        $pessoa->fl_ativo = FALSE;
        $pessoa->store();
        AdiantiCoreApplication::gotoPage('PessoaList');
        TTransaction::close();
    }

    public static function onAtivar($param)
    {
        $action = new TAction(array(__CLASS__, 'ativa'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion('Você tem certeza que deseja ativar este usuário?', $action);
    }

    public function ativa($param)
    {
        TTransaction::open('planosaude');
        $pessoa = new Pessoa($param['id']);
        $pessoa->fl_ativo = TRUE;
        $pessoa->store();
        AdiantiCoreApplication::gotoPage('PessoaList');
        TTransaction::close();
    }

    public function displayAtiva($pessoa)
    {
        if($pessoa->fl_ativo)
        {
            return false;
        }
        return true;
    }

    public function displayDesativa($pessoa)
    {
        if(!$pessoa->fl_ativo)
        {
            return false;
        }
        return true;
    }
    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
