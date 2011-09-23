<?php

class agent_synchronize extends agent
{
    public $get = array('deleted_ref:c:[-_a-zA-Z0-9]+');

    function control()
    {
        if ($this->get->deleted_ref)
        {
            annuaire_manager::deleteFiche($this->get->deleted_ref);
        }
        else annuaire_manager::synchronize();
    }
}
