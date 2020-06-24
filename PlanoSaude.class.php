<?php
/**
 * PlanoSaude Active Record
 */
class PlanoSaude extends TRecord
{
    const TABLENAME = 'public.plano_saude';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
    }
}
