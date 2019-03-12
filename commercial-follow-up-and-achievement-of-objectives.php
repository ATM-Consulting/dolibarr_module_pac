<?php
require('config.php');
dol_include_once('/projet/class/project.class.php');
dol_include_once('/projet/class/task.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/core/lib/usergroups.lib.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/pac/class/followupGoal.class.php');

/*
 * Action
 */

$action = GETPOST('action');

if($action == 'savegoal') {
    $fk_user = GETPOST('fk_user', 'int');
    $fk_cat = GETPOST('fk_cat', 'int');
    $y = GETPOST('y', 'int');
    $m = GETPOST('m', 'int');
    $amount = GETPOST('amount', 'int');

    $followupGoal = new followupGoal($db);
    $followupGoal->fetchFromDate($fk_user, $y, $m, $fk_cat);
    $followupGoal->fk_user = $fk_user;
    $followupGoal->fk_cat = $fk_cat;
    $followupGoal->year = $y;
    $followupGoal->month = $m;
    $followupGoal->amount = $amount;
    if($followupGoal->save($user) > 0) {
        setEventMessage($langs->trans('Saved'));
    }
    else {
        setEventMessage($langs->trans('Error'), 'errors');
        print $followupGoal->lastSql;
    }
}

/*
 * VIEW
 */

llxHeader('', $langs->trans('CommercialFollowUp'), '', '', 0, 0, array(), array('/pac/css/pac.css'));

print_fiche_titre($langs->trans("CommercialFollowUp"));

_print_filtres();
_print_rapport();

llxFooter();

function _get_propales($iduser, $date_deb, $date_fin) {
    global $sortorder, $sortfield, $conf;
    $sortfield = GETPOST('sortfield');
    $sortorder = GETPOST('sortorder');
    $TData = array();

    $catLists = getCategoryChild($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT);

    // A mon avis il faut 3 requête avec un UNION :
    // Pour l'instant le montant réalisé se met dans le mois de cloture si la propale est close.

    // - Toutes les propales (fk_statut > 0) sur datep
    _get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'all');

    // - Toutes les propales signée (fk_statut IN (2,4)) sur date_cloture (ou date_signature à revoir)
    _get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'allSigned');

    // - Toutes les propales non signée (fk_statut = 3) sur date_cloture (ou date_signature à revoir)
    _get_propales_query($TData, $catLists, $iduser, $date_deb, $date_fin, 'allNotSigned');

    return $TData;
}

function prepareTDataFromSql($sql, &$TData, $type, $forceNull = false) {
    global $db;

    $resql = $db->query($sql);
    if($resql) {
        while($line = $db->fetch_object($resql)) {
            // dispatch results

            if(empty($line->fk_categorie)) {
                $line->fk_categorie = 0;
            } // in NULL case

            if($forceNull) {
                $line->fk_categorie = 0;
            } // in forceCatId case

            $line->time = strtotime($line->year.'-'.$line->month.'-01 00:00:00'); // utilisé plus tard juste poour un affichage propre des dates

            $TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$type] = $line; // les résultats brute
            $TData['dates'][$line->year.'-'.$line->month] = new stdClass();// liste des mois
            $TData['dates'][$line->year.'-'.$line->month]->time = $line->time; // utilisé plus tard juste poour un affichage propre des dates
        }
    }
}

function prepareTDataFromSqlHorsCat($sql, &$TData, $type) {
    global $db;

    $resql = $db->query($sql);
    if($resql) {
        while($line = $db->fetch_object($resql)) {
            // dispatch results

            if(empty($line->fk_categorie)) {
                $line->fk_categorie = 0;
            } // in NULL case

            $line->fk_categorie = 0;

            $line->time = strtotime($line->year.'-'.$line->month.'-01 00:00:00'); // utilisé plus tard juste poour un affichage propre des dates

            $TData['data'][$line->fk_categorie][$line->year.'-'.$line->month][$type] = $line; // les résultats brute
            $TData['dates'][$line->year.'-'.$line->month] = new stdClass();// liste des mois
            $TData['dates'][$line->year.'-'.$line->month]->time = $line->time; // utilisé plus tard juste poour un affichage propre des dates
        }
    }
}

function _print_filtres() {
    $Tform = new TFormCore($_SERVER["PHP_SELF"], 'formFiltres', 'GET');
    _get_filtre($Tform);

    $Tform->end();
}

function _get_filtre($form) {
    global $db;

    $formol = new Form($db);

    if(GETPOST('date_deb') == '') $default_date_deb = date('Y-m', strtotime(date('Y-m-d')) - (60 * 60 * 24 * 30));
    if(GETPOST('date_fin') == '') $default_date_fin = date('Y-m');

    print '<div class="tabBar">';
    print '<table>';
    print '<tr>';
    print '<td>Commercial : </td>';
    print '<td>';
    print $formol->select_users(GETPOST('userid'), 'userid', 1);
    print '</td>';
    print '<td>';
    //print $formol->selectarray('type', array('signed'=>$langs->trans('Signed'),'valid'=>$langs->trans('Validated')), GETPOST('type') );
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td>Date de début : </td>';
    print '<td><input type="month" min="month" name="date_deb" value="'.((GETPOST('date_deb')) ? GETPOST('date_deb') : $default_date_deb).'" /></td>';
    print '</tr>';

    print '<tr>';
    print '<td>Date de fin : </td>';
    print '<td><input type="month" min="month" name="date_fin" value="'.((GETPOST('date_fin')) ? GETPOST('date_fin') : $default_date_fin).'" /></td>';
    print '</tr>';

    print '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
    print '</table>';

    print '</div>';
}

function _print_rapport() {
    global $db, $langs, $form;

    $idUser = GETPOST('userid', 'int');
    if($idUser < 1) {
        $idUser = 0;
    }
    $date_d = str_replace('/', '-', GETPOST('date_deb'));
    $date_f = str_replace('/', '-', GETPOST('date_fin'));
    $date_deb = date('Y-m-01', strtotime($date_d));
    $date_fin = date('Y-m-t', strtotime($date_f));

    if(GETPOST('date_deb') == '') $date_deb = date('Y-m-01', strtotime(date('Y-m-01')) - (60 * 60 * 24 * 30));
    if(GETPOST('date_fin') == '') $date_fin = date('Y-m-t');

    $TData = _get_propales($idUser, $date_deb, $date_fin);

    if(empty($TData)) return;

    // INIT DATE INFOS TOTALS
    prepareTData($TData, strtotime($date_deb), strtotime($date_fin), 1);

    print '<div id="goalTableGlobalWrap"><div id="goalTableWrap"><table id="fixedTable" class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

    // TABLE HEAD
    print '<thead>';
    print '<tr class="liste_titre">';
    print '<th></th>';
    foreach($TData['dates'] as $dateInfos) {
        print '<th colspan="3" class="border-left-heavy" style="text-align: center;">'.dol_print_date($dateInfos->time, '%B %Y').'</th>';
    }
    print '<th colspan="3" class="border-left-heavy" style="text-align: center;">'.$langs->trans('Total').'</th>';
    print '</tr>';

    print '<tr class="liste_titre">';
    print '<th class="cellMinWidth" style="z-index: 10">';
    _printParentCat();
    print '</th>';

    foreach($TData['dates'] as $dateInfos) {
        print '<th class="border-left-heavy cellMinWidth" style="text-align: center;">'.$langs->trans('Realized').'</th>';
        print '<th class="border-left-light cellMinWidth" style="text-align: center;">'.$langs->trans('Signed').'</th>';
        print '<th class="border-left-light cellMinWidth" style="text-align: center;">'.$langs->trans('TransformationRatio').'</th>';
    }

    print '<th class="border-left-heavy cellMinWidth" style="text-align: center;">'.$langs->trans('Realized').'</th>';
    print '<th class="border-left-light cellMinWidth" style="text-align: center;">'.$langs->trans('Signed').'</th>';
    print '<th class="border-left-light cellMinWidth" style="text-align: center;">'.$langs->trans('TransformationRatio').'</th>';
    print "</tr>";
    print '</thead>';

    print '<tbody>';

    foreach($TData['data'] as $fk_categorie => $data) {
        $catLabel = $langs->trans('withoutCategory');
        if(! empty($fk_categorie)) {
            $categorie = new Categorie($db);
            $categorie->fetch($fk_categorie);
            $catLabel = $categorie->label;
        }
        print '<tr class="oddeven">';
        print '<th style="text-align: left;">'.$catLabel.'</th>';

        $sector_totalRealised = 0;
        $sector_nbRealised = 0;

        $sector_totalSigned = 0;
        $sector_nbSigned = 0;

        $sector_totalNotSigned = 0;
        $sector_nbNotSigned = 0;

        foreach($TData['dates'] as $dateKey => $dateInfos) {
            $totalRealised = 0;
            $nbRealised = 0;

            $totalSigned = 0;
            $nbSigned = 0;

            $totalNotSigned = 0;
            $nbNotSigned = 0;

            // AFFECTATION DES TOTAUX AUX DATES
            if(! empty($data[$dateKey])) {
                foreach($data[$dateKey] as $type => $object) {
                    if($type == 'all') {
                        // nothing
                        $totalRealised += $object->total_ht;
                        $nbRealised += $object->totalcount;
                    }
                    else if($type == 'allSigned') {
                        $totalSigned += $object->total_ht;
                        $nbSigned += $object->totalcount;
                    }
                    else if($type == 'allNotSigned') {
                        $totalNotSigned += $object->total_ht;
                        $nbNotSigned += $object->totalcount;
                    }
                }
            }

            // taux de transformation montant
            $ratioDetails = $langs->trans('Amount').' : '.displayAmount($totalSigned).' '.$langs->trans('Signed').' / ('.displayAmount($totalSigned).' '.$langs->trans('Signed').' + '.displayAmount($totalNotSigned).' '.$langs->trans('NotSigned').')';
            $transformationRatio = calcRatio($totalSigned, $totalSigned + $totalNotSigned);
            $ratioDetails .= ' = '.$transformationRatio.'%';

            // taux de transformation qty
            $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$nbSigned.' '.$langs->trans('Signed').' / ('.$nbSigned.' '.$langs->trans('Signed').' + '.$nbNotSigned.' '.$langs->trans('NotSigned').')';
            $ratioDetails .= ' = '.calcRatio($nbSigned, $nbSigned + $nbNotSigned).'%';

            print '<td class="border-left-heavy totalRealised" style="text-align: right;">'.displayAmount($totalRealised).'</td>'; //<a target="_blank" href="'.$listLink.'" >'.displayAmount($totalRealised).'</a>
            print '<td class="border-left-light totalSigned" style="text-align: right;">'.displayAmount($totalSigned).'</td>'; // <a href="'.$listLink.'&propal_statut=2,3,4" >'.displayAmount($totalSigned).'</a>
            print '<td class="border-left-light transformationRatio" style="text-align: right;">'.$form->textwithtooltip(price($transformationRatio).'%', $ratioDetails, 3).'</td>';

            // Mise à jour des totaux secteur (ligne)
            $sector_totalRealised += $totalRealised;
            $sector_nbRealised += $nbRealised;

            $sector_totalSigned += $totalSigned;
            $sector_nbSigned += $nbSigned;

            $sector_totalNotSigned += $totalNotSigned;
            $sector_nbNotSigned += $nbNotSigned;

            // Mise à jour des totaux du bloc date
            $TData['dates'][$dateKey]->totalRealised += $totalRealised;
            $TData['dates'][$dateKey]->nbRealised += $nbRealised;

            $TData['dates'][$dateKey]->totalSigned += $totalSigned;
            $TData['dates'][$dateKey]->nbSigned += $nbSigned;

            $TData['dates'][$dateKey]->totalNotSigned += $totalNotSigned;
            $TData['dates'][$dateKey]->nbNotSigned += $nbNotSigned;
        }

        // taux de transformation montant
        $ratioDetails = $langs->trans('Amount').' : '.displayAmount($sector_totalSigned).' '.$langs->trans('Signed').' / ('.displayAmount($sector_totalSigned).' '.$langs->trans('Signed').' + '.displayAmount($sector_totalNotSigned).' '.$langs->trans('NotSigned').')';
        $sector_transformationRatio = calcRatio($sector_totalSigned, $sector_totalSigned + $sector_totalNotSigned);
        $ratioDetails .= ' = '.$sector_transformationRatio.'%';

        // taux de transformation qty
        $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.$sector_nbSigned.' '.$langs->trans('Signed').' + '.$sector_nbNotSigned.' '.$langs->trans('NotSigned').')';
        $ratioDetails .= ' = '.calcRatio($sector_nbSigned, $sector_nbSigned + $sector_nbNotSigned).'%';

        print '<td class="border-left-heavy sector_totalRealised" style="text-align: right;">'.displayAmount($sector_totalRealised).'</td>';
        print '<td class="border-left-light sector_totalSigned" style="text-align: right;">'.displayAmount($sector_totalSigned).'</td>';
        print '<td class="border-left-light sector_transformationRatio" style="text-align: right;">'.$form->textwithtooltip(price($sector_transformationRatio).'%', $ratioDetails, 3).'</td>';
        print '</tr>';
    }
    print '</tbody>';

    print '<tfoot>';

    /*****************
     * TOTAUX
     ***************************/

    // INIT DES LIGNES
    $totalLine['total'] = '<!-- total --><tr class="oddeven">';
    $totalLine['total'] .= '<th style="text-align: right; z-index: 10;" class="border-top-heavy">'.$langs->trans('Total').'</th>';

    $totalLine['totalCumule'] = '<!-- totalCumule --><tr class="oddeven">';
    $totalLine['totalCumule'] .= '<th style="text-align: right;  z-index: 10;" class="border-top-light">'.$langs->trans('TotalCumule').'</th>';

    $totalLine['totalAmountObjectif'] = '<!-- totalAmountObjectif --><tr class="oddeven">';
    $totalLine['totalAmountObjectif'] .= '<th style="text-align: right; z-index: 10;" class="border-top-heavy">'.$langs->trans('TotalObjectif').'</th>';

    $totalLine['totalDiff'] = '<!-- totalAmountObjectif --><tr class="oddeven">';
    $totalLine['totalDiff'] .= '<th style="text-align: right; z-index: 10;" class="border-top-light">'.$langs->trans('TotalObjectifDiff').'</th>';

    $totalLine['totalDiffCumul'] = '<!-- totalAmountObjectif --><tr class="oddeven">';
    $totalLine['totalDiffCumul'] .= '<th style="text-align: right; z-index: 10;" class="border-top-light">'.$langs->trans('TotalObjectifDiffCumul').'</th>';

    $totalLine['totalAmountObjectifCumul'] = '<!-- totalAmountObjectifCumul --><tr class="oddeven" >';
    $totalLine['totalAmountObjectifCumul'] = '<th style="text-align: right; z-index: 10;" class="border-top-heavy">'.$langs->trans('TotalObjectifCumul').'</th>';

    $totalLine['totalObjectifPercent'] = '<!-- totalObjectifPercent --><tr class="oddeven">';
    $totalLine['totalObjectifPercent'] .= '<th style="text-align: right; z-index: 10;" class="border-top-light">'.$langs->trans('TotalObjectifPercent').'</th>';

    $totalLine['totalObjectifPercentCumul'] = '<!-- totalObjectifPercentCumul --><tr class="oddeven">';
    $totalLine['totalObjectifPercentCumul'] .= '<th style="text-align: right; z-index: 10;" class="border-top-light">'.$langs->trans('totalObjectifPercentCumul').'</th>';

    $global_totalRealised = 0;
    $global_nbRealised = 0;
    $global_totalSigned = 0;
    $global_nbSigned = 0;
    $global_totalNotSigned = 0;
    $global_nbNotSigned = 0;
    $global_followupGoal = 0;

    foreach($TData['dates'] as $dateKey => $dateInfos) {
        // taux de transformation montant
        $ratioDetails = $langs->trans('Amount').' : '.displayAmount($dateInfos->totalSigned).' '.$langs->trans('Signed').' / ('.displayAmount($dateInfos->totalSigned).' '.$langs->trans('Signed').' + '.displayAmount($dateInfos->totalNotSigned).' '.$langs->trans('NotSigned').')';
        $dateInfos->transformationRatio = calcRatio($dateInfos->totalSigned, $dateInfos->totalSigned + $dateInfos->totalNotSigned);
        $ratioDetails .= ' = '.$dateInfos->transformationRatio.'%';

        // taux de transformation qty
        $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$dateInfos->nbSigned.' '.$langs->trans('Signed').' / ('.$dateInfos->nbSigned.' '.$langs->trans('Signed').' + '.$dateInfos->nbNotSigned.' '.$langs->trans('NotSigned').')';
        $ratioDetails .= ' = '.calcRatio($dateInfos->nbSigned, $dateInfos->nbSigned + $dateInfos->nbNotSigned).'%';
        //$dateInfos->transformationRatio = calcRatio($dateInfos->nbSigned, $dateInfos->nbSigned + $dateInfos->nbNotSigned);

        $totalLine['total'] .= '<th class="border-left-heavy border-top-heavy" style="text-align: right;">'.displayAmount($dateInfos->totalRealised).'</th>';
        $totalLine['total'] .= '<th class="border-left-light border-top-heavy" style="text-align: right;">'.displayAmount($dateInfos->totalSigned);
        $totalLine['total'] .= '</th>';

        $totalLine['total'] .= '<th class="border-left-light border-top-heavy" style="text-align: right;">'.$form->textwithtooltip(price($dateInfos->transformationRatio).'%', $ratioDetails, 3).'</th>';

        $followupGoal = followupGoal::getAmount($idUser, date('Y', $dateInfos->time), date('m', $dateInfos->time));
        $goalRatio = calcRatio($dateInfos->totalSigned, $followupGoal);
        $totalLine['totalObjectifPercent'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'"  style="text-align:center;" colspan="3" >';
        $totalLine['totalObjectifPercent'] .= _getGoalField($idUser, $dateInfos, $followupGoal, 0, 1);
        $totalLine['totalObjectifPercent'] .= '</th>';

        $totalLine['totalAmountObjectif'] .= '<th class="border-left-heavy border-top-heavy '.percentClass($goalRatio).'"  style="text-align:center;" colspan="3" >';
        $totalLine['totalAmountObjectif'] .= _getGoalField($idUser, $dateInfos, $followupGoal, 1, 0);
        $totalLine['totalAmountObjectif'] .= '</th>';

        $totalLine['totalDiff'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'"  style="text-align:center;" colspan="3" >';
        $totalLine['totalDiff'] .= displayAmount($dateInfos->totalSigned - $followupGoal);
        $totalLine['totalDiff'] .= '</th>';

        $global_totalRealised += $dateInfos->totalRealised;
        $global_nbRealised += $dateInfos->nbRealised;
        $global_totalSigned += $dateInfos->totalSigned;
        $global_nbSigned += $dateInfos->nbSigned;
        $global_totalNotSigned += $dateInfos->totalNotSigned;
        $global_nbNotSigned += $dateInfos->nbNotSigned;
        $global_followupGoal += $followupGoal;

        // CUMUL

        $goalRatio = calcRatio($global_totalSigned, $global_followupGoal);

        $totalLine['totalAmountObjectifCumul'] .= '<th class="border-left-heavy border-top-heavy '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';
        $totalLine['totalAmountObjectifCumul'] .= displayAmount($global_followupGoal);
        $totalLine['totalAmountObjectifCumul'] .= '</th>';

        $goalRatioDetails = $langs->trans('Goal').': '.displayAmount($global_totalSigned).' / '.displayAmount($global_followupGoal).' = '.$goalRatio;
        $totalLine['totalObjectifPercentCumul'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';
        $totalLine['totalObjectifPercentCumul'] .= '<div class="goalResume">'.$form->textwithtooltip($goalRatio.'%', $goalRatioDetails, 3).'</div>';
        $totalLine['totalObjectifPercentCumul'] .= '</th>';

        $totalLine['totalDiffCumul'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';
        $totalLine['totalDiffCumul'] .= displayAmount($global_totalSigned - $global_followupGoal);
        $totalLine['totalDiffCumul'] .= '</th>';
    }

    /*****************
     * Dernier bloc de total
     ***************************/

    // taux de transformation montant
    $ratioDetails = $langs->trans('Amount').' : '.displayAmount($global_totalSigned).' '.$langs->trans('Signed').' / ('.displayAmount($global_totalSigned).' '.$langs->trans('Signed').' + '.displayAmount($global_totalNotSigned).' '.$langs->trans('NotSigned').')';
    $global_transformationRatio = calcRatio($global_totalSigned, $global_totalSigned + $global_totalNotSigned);
    $ratioDetails .= ' = '.$global_transformationRatio.'%';

    // taux de transformation qty
    $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.$global_nbSigned.' '.$langs->trans('Signed').' + '.$global_nbNotSigned.' '.$langs->trans('NotSigned').')';
    $ratioDetails .= ' = '.calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned).'%';
    //$$global_transformationRatio = calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned);

    $totalLine['total'] .= '<th class="border-left-heavy border-top-heavy" style="text-align: right;">'.displayAmount($global_totalRealised).'</th>';
    $totalLine['total'] .= '<th class="border-left-light border-top-heavy" style="text-align: right;">'.displayAmount($global_totalSigned).'</th>';
    $totalLine['total'] .= '<th class="border-left-light border-top-heavy" style="text-align: right;">'.$form->textwithtooltip(price($global_transformationRatio).'%', $ratioDetails, 3).'</th>';

    $totalLine['totalObjectifPercent'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';

    if(! empty($followupGoal)) {
        $goalRatio = calcRatio($global_totalSigned, $global_followupGoal);
        $goalRatioDetails = $langs->trans('Goal').': '.$global_totalSigned.' / '.$global_followupGoal.' = '.$goalRatio;
        $totalLine['totalObjectifPercent'] .= $form->textwithtooltip(price($goalRatio).'%', $goalRatioDetails, 3);
    }
    $totalLine['totalObjectifPercent'] .= '</th>';

    $totalLine['totalAmountObjectif'] .= '<th class="border-left-heavy border-top-heavy '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';
    $totalLine['totalAmountObjectif'] .= displayAmount($global_followupGoal);
    $totalLine['totalAmountObjectif'] .= '</th>';

    $totalLine['totalDiff'] .= '<th class="border-left-heavy border-top-light '.percentClass($goalRatio).'" style="text-align: center;" colspan="3">';
    $totalLine['totalDiff'] .= displayAmount($global_totalSigned - $global_followupGoal);
    $totalLine['totalDiff'] .= '</th>';

    // CUMUL TOTAL LINE

    $global_totalRealised = 0;
    $global_nbRealised = 0;
    $global_totalSigned = 0;
    $global_nbSigned = 0;
    $global_totalNotSigned = 0;
    $global_nbNotSigned = 0;

    foreach($TData['dates'] as $dateKey => $dateInfos) {
        $global_totalRealised += $dateInfos->totalRealised;
        $global_nbRealised += $dateInfos->nbRealised;
        $global_totalSigned += $dateInfos->totalSigned;
        $global_nbSigned += $dateInfos->nbSigned;
        $global_totalNotSigned += $dateInfos->totalNotSigned;
        $global_nbNotSigned += $dateInfos->nbNotSigned;

        // taux de transformation montant
        $ratioDetails = $langs->trans('Amount').' : '.displayAmount($global_totalSigned).' '.$langs->trans('Signed').' / ('.displayAmount($global_totalSigned).' '.$langs->trans('Signed').' + '.displayAmount($global_totalNotSigned).' '.$langs->trans('NotSigned').')';
        $global_transformationRatio = calcRatio($global_totalSigned, $global_totalSigned + $global_totalNotSigned);
        $ratioDetails .= ' = '.$global_transformationRatio.'%';

        // taux de transformation qty
        $ratioDetails .= '<br/>'.$langs->trans('Number').' : '.$langs->trans('Signed').' / ('.$global_nbSigned.' '.$langs->trans('Signed').' + '.$global_nbNotSigned.' '.$langs->trans('NotSigned').')';
        $ratioDetails .= ' = '.calcRatio($global_nbSigned, $global_nbSigned + $global_nbNotSigned).'%';

        $totalLine['totalCumule'] .= '<th class="border-left-heavy border-top-light" style="text-align: right;">'.displayAmount($global_totalRealised).'</th>';
        $totalLine['totalCumule'] .= '<th class="border-left-light border-top-light" style="text-align: right;">'.displayAmount($global_totalSigned).'</th>';
        $totalLine['totalCumule'] .= '<th class="border-left-light border-top-light" style="text-align: right;">'.$form->textwithtooltip(price($global_transformationRatio).'%', $ratioDetails, 3).'</th>';
    }

    $totalLine['totalCumule'] .= '<th class="border-left-heavy border-top-light" colspan="3"></th>';

    $totalLine['totalAmountObjectifCumul'] .= '<th class="border-left-heavy border-top-heavy" style="text-align: center;" colspan="3"></th>';
    $totalLine['totalObjectifPercentCumul'] .= '<th class="border-left-heavy border-top-light" colspan="3"></th>';
    $totalLine['totalDiffCumul'] .= '<th class="border-left-heavy border-top-light" colspan="3"></th>';

    print $totalLine['total']."</tr>";
    print $totalLine['totalCumule']."</tr>";

    print $totalLine['totalAmountObjectif']."</tr>";
    print $totalLine['totalDiff']."</tr>";
    print $totalLine['totalObjectifPercent']."</tr>";

    print $totalLine['totalAmountObjectifCumul']."</tr>";
    print $totalLine['totalDiffCumul']."</tr>";
    print $totalLine['totalObjectifPercentCumul']."</tr>";

    print '</tfoot>';
    print '</table></div></div>';

    printGoalJs();
}

function calcRatio($nbSigned, $nbRealised) {
    $transformationRatio = 0;

    if(empty($nbRealised)) {
        return 0; // prevent division by 0
    }

    if(! empty($nbSigned)) {
        $transformationRatio = $nbSigned / $nbRealised * 100;
        $transformationRatio = round($transformationRatio, 2);
    }

    return $transformationRatio;
}

function insertBefore($input, $index, $newKey, $element) {
    if(! array_key_exists($index, $input)) {
        throw new Exception("Index not found");
    }
    $tmpArray = array();
    foreach($input as $key => $value) {
        if($key === $index) {
            $tmpArray[$newKey] = $element;
        }
        $tmpArray[$key] = $value;
    }

    return $tmpArray;
}

function prepareTData(&$TData, $date_deb, $date_fin, $sortcat = 0) {
    global $db;
    $lastM = date('m', $date_deb);
    $lastY = date('Y', $date_deb);

    // SORT CATEGORY
    if($sortcat) {
        $Tlabels = array();
        foreach($TData['data'] as $fk_categorie => $data) {
            if($fk_categorie > 0) {
                $category = new Categorie($db);
                $res = $category->fetch($fk_categorie);
                if($res > 0) {
                    $Tlabels[$category->id] = $category->label;
                }
            }
        }

        asort($Tlabels);
        $Tlist = array();

        $TdataTemps = $TData['data'];
        $TData['data'] = array();

        foreach($Tlabels as $id => $label) {
            $TData['data'][$id] = $TdataTemps[$id];
        }

        if(! empty($TdataTemps[0])) {
            $TData['data'][0] = $TdataTemps[0];
        }
    }

    foreach($TData['dates'] as $dateKey => & $dateInfos) {
        $curM = dol_print_date($dateInfos->time, '%m');
        $curY = dol_print_date($dateInfos->time, '%Y');

        if(! empty($lastM) && $curM > $lastM + 1) {
            for($i = $lastM + 1; $i < $curM; $i++) {

                $newCol = new stdClass();
                $newCol->time = mktime(0, 0, 1, $i, 1, $curY);
                $newCol->totalRealised = 0;
                $newCol->nbRealised = 0;

                $newCol->totalSigned = 0;
                $newCol->nbSigned = 0;

                $newCol->transformationRatio = 0;

                $TData['dates'] = insertBefore($TData['dates'], $dateKey, $curY.'-'.$i, $newCol);
            }
        }

        $dateInfos->totalRealised = 0;
        $dateInfos->nbRealised = 0;

        $dateInfos->totalSigned = 0;
        $dateInfos->nbSigned = 0;

        $dateInfos->transformationRatio = 0;

        $lastM = dol_print_date($dateInfos->time, '%m');
        $lastY = dol_print_date($dateInfos->time, '%Y');
    }

    $curM = dol_print_date($date_fin, '%m');
    $curY = dol_print_date($date_fin, '%Y');
    // Date de fin manquante
    if(! empty($lastM) && $curM > $lastM) {
        for($i = $lastM + 1; $i <= $curM; $i++) {

            $newCol = new stdClass();
            $newCol->time = mktime(0, 0, 1, $i, 1, $curY);
            $newCol->totalRealised = 0;
            $newCol->nbRealised = 0;

            $newCol->totalSigned = 0;
            $newCol->nbSigned = 0;

            $newCol->transformationRatio = 0;

            if(empty($TData['dates'][$curY.'-'.$i])) {
                $TData['dates'][$curY.'-'.$i] = $newCol;
            }
        }
    }
}

function getCategoryChild($cat, $deep = 0, $returnObject = 0, $sort = 0) {
    global $db;

    dol_include_once('categories/class/categorie.class.php');

    $Tlist = array();

    $category = new Categorie($db);
    $res = $category->fetch($cat);

    $Tfilles = $category->get_filles();
    if(! empty($Tfilles) && $Tfilles > 0) {
        foreach($Tfilles as &$fille) {
            $Tlist[] = $fille->id;

            $Tchild = getCategoryChild($fille->id, $deep++);
            if(! empty($Tchild)) {
                $Tlist = array_merge($Tlist, $Tchild);
            }
        }
    }

    if($sort) {
        $Tlabels = array();
        foreach($Tlist as $id) {
            $category = new Categorie($db);
            $res = $category->fetch($id);
            if($res > 0) {
                $Tlabels[$category->id] = $category->label;
            }
        }

        asort($Tlabels);
        $Tlist = array();
        foreach($Tlabels as $id => $label) {
            $Tlist[] = $id;
        }
    }

    return $Tlist;
}

function _get_propales_query(&$TData, $catLists, $iduser, $date_deb, $date_fin, $type = 'all') {
    global $conf;

    // A mon avis il faut 3 requête avec un UNION :
    if($type == 'all') {
        // - Toutes les propales (fk_statut > 0) sur datep
        $date_field = 'p.datep';
        $sqlStatus = ' AND p.fk_statut > 0';
    }
    else if($type == 'allSigned') {
        // - Toutes les propales signée (fk_statut IN (2,4)) sur date_cloture (ou date_signature à revoir)
        $date_field = 'COALESCE(NULLIF(pex.date_signature,\'\'), p.date_cloture) ';
        $sqlStatus = ' AND p.fk_statut IN (2,4)';
    }
    else if($type == 'allNotSigned') {
        // - Toutes les propales non signée (fk_statut = 3) sur date_cloture (ou date_signature à revoir)
        $date_field = 'p.date_cloture';
        $sqlStatus = ' AND p.fk_statut = 3';
    }
    else {
        return false;
    }

    // Pour l'instant le montant réalisé se met dans le mois de cloture si la propale est close.propal

    $sqlSelect = 'SELECT';
    $sqlSelect .= '	SUM(p.total_ht) total_ht,';
    $sqlSelect .= '	count(p.rowid) totalcount,';
    $sqlSelect .= '	MONTH('.$date_field.') month,';
    $sqlSelect .= '	YEAR('.$date_field.') year';

    $sqlFrom = ' FROM '.MAIN_DB_PREFIX.'propal p';

    $sqlJoin = ' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'propal_extrafields pex ON (pex.fk_object = p.rowid)';
    if($iduser > 0) $sqlJoin .= ' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'element_contact ec ON (ec.element_id = p.rowid)';

    $sqlWhere = ' WHERE 1';

    if($iduser > 0) $sqlWhere .= ' AND ec.fk_c_type_contact = 31 AND ec.fk_socpeople = '.$iduser;

    $sqlWhere .= ' AND ('.$date_field.' BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")';

    $sqlWhere .= $sqlStatus;

    $sqlCatFilter = '';

    $sqlGroup = ' GROUP BY MONTH('.$date_field.'), YEAR('.$date_field.')';

    $sqlOrder = ' ORDER BY month, year ASC';

    // prepare with parent cat
    if(! empty($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT) && ! empty($catLists)) {

        $sql = $sqlSelect.', cs.fk_categorie fk_categorie';
        $sql .= $sqlFrom;
        $sql .= $sqlJoin.' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'categorie_societe cs ON (cs.fk_soc = p.fk_soc)';
        $sql .= $sqlWhere;
        $sql .= $sqlCatFilter.' AND cs.fk_categorie IN ('.implode(',', $catLists).')';
        $sql .= $sqlGroup.', cs.fk_categorie';
        $sql .= $sqlOrder;
    }
    else {
        $sql = $sqlSelect.$sqlFrom.$sqlJoin.$sqlWhere.$sqlCatFilter.$sqlGroup.$sqlOrder;
    }

    prepareTDataFromSql($sql, $TData, $type);

    // cherche et prepare les tiers hors catégorie
    if(! empty($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT)) {

        $sql = $sqlSelect;
        $sql .= $sqlFrom;
        $sql .= $sqlJoin;
        $sql .= $sqlWhere;
        $sql .= $sqlCatFilter.' AND p.fk_soc NOT IN (SELECT sqcs.fk_soc FROM '.MAIN_DB_PREFIX.'categorie_societe sqcs WHERE sqcs.fk_categorie IN ('.implode(',', $catLists).'))';
        $sql .= $sqlGroup;
        $sql .= $sqlOrder;

        prepareTDataFromSqlHorsCat($sql, $TData, $type);
    }

    return $TData;
}

function printGoalJs() {
    global $user;

    print '<script src="'.dol_buildpath('/pac/js/fixed_table.min.js', 2).'"></script>';

    print '<script type="text/javascript" >$(document).ready(function() {';

    if(! empty($user->rights->pac->changeGoal)) {

        print "
	        	$('.goalResume').click(function (e) {
	        		var idtag = $(this).data('idtag');
	        		$('#goalformwrap_' + idtag).show();
	        	});
	        	
	        	$('.annuleformgoal').click(function (e) {
		        	e.preventDefault();
	        		var idtag = $(this).data('idtag');
	        		$('#goalformwrap_' + idtag).hide();
	        	});
			";
    }

    print "$('#goalTableWrap').width($( window ).width() - $('#id-left').outerWidth() - 25 ) ;";
    print "$('#fixedTable').tableHeadFixer({'head' : true, 'left' : 1, foot:true, 'z-index': 10 }); ";

    print "$(window).bind('resize', function () {";
    print "$('#goalTableWrap').width($( window ).width() - $('#id-left').outerWidth() - 25 ) ;";
    print "});";

    print '}); </script>';
}

function _getGoalField($idUser, $dateInfos, $followupGoal, $addForm = 1, $ratio = 0) {
    global $langs, $user, $form;

    $goalRatio = calcRatio($dateInfos->totalSigned, $followupGoal);
    $goalRatioDetails = $langs->trans('Goal').': '.displayAmount($dateInfos->totalSigned).' / '.displayAmount($followupGoal).' = '.$goalRatio;

    if($ratio) {
        $html = price($goalRatio).'%';
    }
    else {
        $html = displayAmount($followupGoal);
    }

    if(! empty($user->rights->pac->changeGoal) && $addForm) {
        $idTag = $idUser.'-'.date('Y', $dateInfos->time).'-'.date('m', $dateInfos->time).'-0';

        $goalRatioDetails .= '<br/><strong>'.$langs->trans('ClicToChangeGoal').'</strong>';

        $return = '<div id="goalResume_'.$idTag.'" class="goalResume" data-idtag="'.$idTag.'" >'.$form->textwithtooltip($html, $goalRatioDetails, 3).'</div>';

        $return .= '<div id="goalformwrap_'.$idTag.'" class="goalformwrap"  style="display:none;"  >';
        $url = "http".(($_SERVER['SERVER_PORT'] == 443) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'#goalResume_'.$idTag;
        $Tform = new TFormCore($url, ''.$idTag, 'POST');
        $return .= $Tform->begin_form($url, 'goalform_'.$idTag, 'POST', false, '');

        $return .= '<input name="y" type="hidden" value="'.date('Y', $dateInfos->time).'" />';
        $return .= '<input name="m" type="hidden" value="'.date('m', $dateInfos->time).'" />';
        $return .= '<input name="fk_user" type="hidden" value="'.$idUser.'" />';
        $return .= '<input name="fk_cat" type="hidden" value="0" />';
        $return .= '<label>'.$langs->trans('EditGoal').'</label><br/>';
        $return .= '<input name="amount" class="goalfield" type="number" min="0" step="1" value="'.$followupGoal.'" />';
        $return .= '<br/><button class="butAction" type="submit" name="action" value="savegoal" >'.$langs->trans('Save').'</button>';
        $return .= '<button class="butAction annuleformgoal" data-idtag="'.$idTag.'" >'.$langs->trans('Cancel').'</button>';

        $return .= "</form>";
        $return .= '</div>';
    }
    else {
        $return = '<div class="goalResume" >'.$form->textwithtooltip($html, $goalRatioDetails, 3).'</div>';
    }

    return $return;
}

function _printGoalField($idUser, $dateInfos, $followupGoal, $addForm = 1, $ratio = 0) {
    print _getGoalField($idUser, $dateInfos, $followupGoal, $addForm, $ratio);
}

function _printParentCat() {
    global $db, $conf, $langs;

    $category = new Categorie($db);
    $res = $category->fetch($conf->global->PAC_COMERCIAL_FOLLOWUP_PARENT_CAT);
    if($res > 0) {
        print $category->label;
    }
    else {
        print $langs->trans('Category');
    }
}

function displayAmount($amount) {
    global $conf;

    if(! empty($conf->global->PAC_DISPLAY_K_AMOUNT)) {
        $return = round($amount / 1000).'K';
        if($amount < 500 && $amount > 0) {
            $return = '~'.$return;
        }

        return $return;
    }

    return round($amount);
}

function percentClass($percent) {
    if($percent < 80) {
        return 'goal-danger';
    }
    else if($percent < 95) {
        return 'goal-warning';
    }
    else if($percent < 100) {
        return 'goal-idle';
    }
    else {
        return 'goal-success';
    }
}
