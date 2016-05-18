<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	
	
	
	llxHeader('',$langs->trans('bestPropales'));
	
	print dol_get_fiche_head($langs->trans('bestPropales'));
	print_fiche_titre($langs->trans("bestPropales"));
	_print_rapport();
	
	function _get_propales(){
		global $db;
		
		$sql = "SELECT p.rowid AS rowid, p.fk_soc AS client, pext.proba AS proba FROM ".MAIN_DB_PREFIX."propal p ";
		$sql .= "INNER JOIN ".MAIN_DB_PREFIX."propal_extrafields pext ON p.rowid=pext.fk_object ";
		$sql .= "WHERE p.fk_statut=1 ";
		$sql .= "ORDER BY  pext.proba DESC , p.total_ht DESC  ";
		$sql .= "LIMIT 0, 10";

		$resql = $db->query($sql);
		
		$TData = array();
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$proba='';
				if ($line->proba==4)$proba='100%';
				else if ($line->proba==3)$proba='75%';
				else if ($line->proba==2)$proba='50%';
				else if ($line->proba==1)$proba='25%';
				else $proba='';
				
				$TData[] = array(
					"rowid"          => $line->rowid,
					"idClient"         => $line->client,
					"proba"          => $proba
				);
			}
		}
		return $TData;
	}
	
	function _print_rapport(){
		global $db;
		
		$TData = _get_propales();
		
		?>
		<style type="text/css">
		table#rapport_depassement td,table#rapport_depassement th {
			white-space: nowrap;
			border-right: 1px solid #D8D8D8;
			border-bottom: 1px solid #D8D8D8;
		}
		</style>
		
		<div style="padding-bottom: 25px;">
			<table id="rapport_depassement" class="noborder" width="100%">
				<thead>
					<tr style="text-align:left;" class="liste_titre nodrag nodrop">
						<th class="liste_titre">Proposition commerciale</th>
						<th class="liste_titre">Société</th>
						<th class="liste_titre">Date de livraison</th>
						<th class="liste_titre">Probabilité</th>
						<th class="liste_titre" style="text-align: right">Montant</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$montant_total=0;
					foreach ($TData as $line){
						
						$montant_total += $propale->total_ht;
						$propale = new Propal($db);
						$propale->fetch($line["rowid"]);
						
						
						$societe = new Societe($db);
						$societe->fetch($line['idClient']);
						?>
						<tr>
							<td><?php echo $propale->getNomUrl(1) ?></td>
							<td><?php echo $societe->getNomUrl(1) ?></td>
							<td><?php echo (!empty($propale->date_livraison)) ? date('d/m/Y', $propale->date_livraison) : '' ?></td>
							<td><?php echo $line['proba']; ?></td>
							<td style="text-align: right"><?php echo price($propale->total_ht) ;?></td>
						</tr>
					<?php	
					}
					?>
					<tfoot>
		                <tr style="font-weight: bold;">
		                    <td>Total</td>
		                    <td></td>
		                    <td></td>
		                    <td></td>
		                    <td style="text-align: right"><?php echo price($montant_total) ?></td>
		            	</tr>
		            </tfoot>
				</tbody>
			</table>
		</div>
		
		<?php 
		
		foreach ($TData as $line){
			$societe = new Societe($db);
			$societe->fetch($line['idClient']);
			
			
			
		}
		
	}
