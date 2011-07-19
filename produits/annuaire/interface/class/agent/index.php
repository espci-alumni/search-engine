<?php

class extends agent
{
    public $get = array(
        'q', // requete pour le moteur de recherche
        'tag', // tag pour filtrage des résultats
        'p:i:2' => 1 // numéro de page à afficher
    );

    protected

    $loopFiche = 'loop_search_index',

    $maxage = 3600,

    $subset = false,
    $tags = array(),

    $niveauMin,
    $dynamic,
    $histoCumule;


    function compose($o)
    {
        if ('' !== (string) $this->get->q || $this->get->p) p::setGroup('private');
        else $this->watch[] = 'fiche/0';

        // Calcule le nombre total de fiches accessibles
        $sql = 'SELECT COUNT(*) FROM ' . annuaire::$fiche_table;
        $o->nb_fiches = DB()->queryOne($sql);

        // Crée le formulaire
        $form = new pForm($o, '', false);
        $form->setPrefix('');
        $form->add('text', 'q');

        // Etapes de composition des résultats
        $o = $this->prepareSubset($o);
        $o = $this->prepareResults($o);
        $o = $this->filterResults($o);
        $o = $this->prepareFilter($o);

        return $o;
    }

    function prepareSubset($o)
    {
        if ($this->get->tag)
        {
            // Prépare une table temporaire pour filtrer en fonction des tags sélectionnés

            $sql = explode('_', $this->get->tag);
            array_shift($sql);
            $this->tags = $sql;
            $sql = array_map(array($this, 'prepareTag'), $sql);

            $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS subsettmp ENGINE=HEAP
                    SELECT i.fiche_id
                    FROM ' . annuaire::$mot_fiche_table . ', ' . annuaire::$mot_table . '
                    WHERE i.mot_id = m.mot_id
                        AND i.tag=1
                        AND m.mot IN("' . implode('","', $sql) . '")
                    GROUP BY i.fiche_id HAVING COUNT(DISTINCT i.mot_id)=' . count($sql);
            DB()->query($sql);

            $this->subset = true;
        }

        return $o;
    }

    function prepareTag($tag)
    {
        $tag = addslashes($tag);

        if ('s' !== substr($tag, -1)) $tag .= '","' . $tag . 's';

        return $tag;
    }

    function prepareResults($o)
    {
        $loop = $this->loopFiche;

        $loop = new $loop(
            $this->get->q,
            $this->subset,
            $this->get->p
        );

        $loop->addHighlight(annuaire::$tagFields, $this->tags);

        $o->fiches = $loop;
        $o->results_per_page = $loop->resultsPerPage;

        return $o;
    }

    function filterResults($o)
    {
        $db = DB();

        $o->nb_resultats = $db->queryOne('SELECT COUNT(*) FROM searchtmp');

        $sql = "SELECT GROUP_CONCAT(f.fiche_ref ORDER BY s.order_key SEPARATOR ',')
            FROM " . annuaire::$fiche_table . ", searchtmp s
            WHERE f.fiche_id=s.fiche_id
            GROUP BY ''";

        if ($ficheList = $db->queryOne($sql)) $o->ficheList = ',' . $ficheList . ',';

        return $o;
    }

    function prepareFilter($o)
    {
        if (!isset($o->fiches)) return $o;

        $db = DB();

        $subset = '';
        if ($o->fiches->hasQuery) $subset = 'searchtmp';
        else if ($this->subset  ) $subset = 'subsettmp';

        // Nombre de fiches à lier aux tags
        $nb = isset($o->nb_resultats) ? $o->nb_resultats : 0;
        if ($subset)
        {
            $sql = "SELECT COUNT(*) FROM {$subset}";
            $nb = $db->queryOne($sql);
        }
        else $nb = 0;

        // Initialise les tags avec les mots filtrants sélectionnés
        $tags = array();
        foreach ($this->tags as $sql) $tags[$sql] = $nb;

        $histo = array();
        count($tags) && $histo[$nb] = count($tags);

        $niveauMin = PHP_INT_MAX;
        $niveauMax = $nb ? $nb : 0;


        $sql = 'SELECT COALESCE(m.tag, m.mot) AS mot, COUNT(*) AS nb
            FROM ' . annuaire::$mot_table . ', ' . annuaire::$mot_fiche_table . ($subset ? ", {$subset} s" : '') . '
            WHERE m.mot_id=i.mot_id AND i.tag=1' . ($subset ? ' AND i.fiche_id=s.fiche_id' : '') . '
            GROUP BY i.mot_id' . ($subset ? " HAVING nb < {$nb}" : '') . '
            ORDER BY nb DESC';
        $db->setLimit(annuaire::$tagMaxNb);
        $result = $db->query($sql);

        while ($row = $result->fetchRow())
        {
            $tags[ $row->mot ] = $row->nb;

            isset($histo[$row->nb]) || $histo[$row->nb] = 0;
            ++$histo[$row->nb];
            $row->nb < $niveauMin && $niveauMin = $row->nb;
            $row->nb > $niveauMax && $niveauMax = $row->nb;
        }

        if ($tags)
        {
            uksort($tags, 'strnatcasecmp');

            $tags = new loop_array($tags, array($this, 'filterMotclef'));
            $tags = new loop_tag($tags, 'nb', 'niveau', annuaire::$tagSizeNb);
            $tags->setHisto($histo, $niveauMin, $niveauMax);

            $o->tags = $tags;
        }

        return $o;
    }

    function filterMotClef($data)
    {
        return (object) array(
            'text' => $data->KEY,
            'nb' => $data->VALUE,
        );
    }
}
