<?php

class agent_atlas_gmapPipes extends agent
{
    const contentType = 'text/javascript';

    function control() {}

    function compose($o)
    {
        echo 'P$annuaire_ficheUrl='; pipe_annuaire_ficheUrl::js();
        echo 'P$annuaire_photoUrl='; pipe_annuaire_photoUrl::js();

        return $o;
    }
}
