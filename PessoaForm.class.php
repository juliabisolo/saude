<?php
/**
 * PessoaForm Registration
 */
class PessoaForm extends TPage
{
    protected $form; // form
    
    use Adianti\Base\AdiantiStandardFormTrait; // Standard form methods
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('planosaude');   // defines the database
        $this->setActiveRecord('Pessoa');     // defines the active record

        // creates the form
        $this->form = new BootstrapFormBuilder('form_Pessoa');
        $this->form->setFormTitle('Pessoa');
        $this->form->setFieldSizes('100%');
        $this->form->generateAria(); // automatic aria-label
        
        // create the form fields
        $id             = new THidden('id');
        $nome           = new TEntry('nome');
        $cpf            = new TEntry('cpf');
        $dt_nascimento  = new TDate('dt_nascimento');
        $endereco       = new TEntry('endereco');
        $telefone       = new TEntry('telefone');
        $historico      = new TText('historico');
        $ref_plano_saude  = new TDBUniqueSearch('ref_plano_saude', 'planosaude', 'PlanoSaude', 'id', 'descricao');

        //personaliza campos
        $cpf->setMask('999.999.999-99');
        $dt_nascimento->setDatabaseMask('yyyy/mm/dd');
        $dt_nascimento->setMask('dd/mm/yyyy');
        $telefone->setMask('(99)99999-9999');
        $historico->setSize('100%', 170);

        //valida se os campos foram preenchidos corretamente
        $nome->addValidation('Nome', new TRequiredValidator); // obrigatório
        $cpf->addValidation('CPF', new TCPFValidator); // valida cpf
        $cpf->addValidation('CPF', new TRequiredValidator); // obrigatório
        $dt_nascimento->addValidation('Data de nascimento', new TRequiredValidator); // obrigatório
        $telefone->addValidation('Telefone', new TMinLengthValidator, array(11)); // não pode ter menos de 11 caracteres
        $telefone->addValidation('Telefone', new TRequiredValidator); // obrigatório
        $ref_plano_saude->addValidation('Plano de saúde', new TRequiredValidator); // obrigatório
        $endereco->addValidation('Endereço', new TRequiredValidator); // obrigatório

        //adiciona os campos no form
        $this->form->addFields([$id]);

        $row = $this->form->addFields([ new TLabel('Nome*'), $nome]);

        $row = $this->form->addFields([ new TLabel('CPF*'), $cpf]);
        $row->layout = ['col-sm-4']; //comprimento do campo, setSize não funcionou

        $row = $this->form->addFields([ new TLabel('Data de nascimento*'), $dt_nascimento]);
        $row->layout = ['col-sm-4'];

        $row = $this->form->addFields([ new TLabel('Telefone*'), $telefone]);
        $row->layout = ['col-sm-4'];

        $row = $this->form->addFields([ new TLabel('Plano de saúde*'), $ref_plano_saude]);
        $row->layout = ['col-sm-4'];
        
        $row = $this->form->addFields([ new TLabel('Endereço*'), $endereco]);

        $row = $this->form->addFields([ new TLabel('Histórico*'), $historico]);

        $this->form->addAction(_t('Save'),   new TAction(array($this, 'onSave')), 'far:check-circle green');
        $this->form->addAction(_t('Cancel'), new TAction(array('PessoaList', 'onReload')), 'far:times-circle red');
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        parent::add($vbox);
    }

    public function onEdit($param)
    {
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (isset($param['key']))
            {
                // get the parameter $key
                $key=$param['key'];
                
                // open a transaction with database
                TTransaction::open($this->database);
                
                $class = $this->activeRecord;
                
                // instantiates object
                $object = new $class($key);

                $dateFormatted = $this->formatDateEdit($object->dt_nascimento);
                $object->dt_nascimento = $dateFormatted;
                
                // fill the form with the active record data
                $this->form->setData($object);

                // close the transaction
                TTransaction::close();
                
                return $object;
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function formatDateEdit($date)
    {
        $timestamp = strtotime($date);
        $dateFormatted = date("d/m/Y", $timestamp);

        return $dateFormatted; 
    }

    public function onReload($param)
    {
        parent::onReload($param);
    }

    public function onSave($param)
    {
        try
        {
            if (empty($this->database))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', AdiantiCoreTranslator::translate('Database'), 'setDatabase()', AdiantiCoreTranslator::translate('Constructor')));
            }
            
            if (empty($this->activeRecord))
            {
                throw new Exception(AdiantiCoreTranslator::translate('^1 was not defined. You must call ^2 in ^3', 'Active Record', 'setActiveRecord()', AdiantiCoreTranslator::translate('Constructor')));
            }

            // open a transaction with database
            TTransaction::open($this->database);

            // get the form data
            $object = $this->form->getData($this->activeRecord);

            // fill the form with the active record data
            $this->form->setData($object);

            if(($this->formatDateEdit($object->dt_nascimento)) > date('d/m/Y'))
            {
                PessoaForm::alertDataNascimento($param);
                return;
            }

            // validate data
            $this->form->validate();

            // stores the object
            $object->store();

            $posAction = new TAction(array('PessoaList', 'onReload'));
            
            // close the transaction
            TTransaction::close();
            
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $posAction);
            
            return $object;
        }
        catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData();
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public static function alertDataNascimento($param)
    {       
        // shows a dialog to the user
        new TMessage('error', 'A data de nascimento deve ser menor ou igual ao dia atual');
    }
}
