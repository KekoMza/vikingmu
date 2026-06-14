		<div class="page-heading" style="background-image: url(<?php echo __PATH_TEMPLATE_IMG__; ?>bg-rank.jpg);    background-position: 50% 25%;">
			<div class="container">
				<div class="row">
					<div class="col-md-10 offset-md-1">
						<h1 class="page-heading__title">Ranking <span class="highlight"><?php echo''.lang('menu_rank_char',true).'';?></span></h1>
						<ol class="page-heading__breadcrumb breadcrumb">
							<li class="breadcrumb-item"><a href="<?php echo''.__BASE_URL__.'';?>"><i class="fa fa-home"></i></a></li>
							<li class="breadcrumb-item"><a href="<?php echo''.__BASE_URL__.'';?>rankings">Players</a></li>
							<li class="breadcrumb-item active" aria-current="page"><?php echo''.$cData[1].'';?> </li>
						</ol>
					</div>
				</div>
			</div>
		</div>
		<!-- Page Heading / End -->

<div class="site-content">
    <div class="container">
		<div class="row">
<?php
loadModuleConfigs('profiles');
if(mconfig('active')) {
	if(check_value($_GET['req'])) {
		try {
			$weProfiles = new weProfiles();
			$weProfiles->setType("player");
			$weProfiles->setRequest($_GET['req']);
			$cData = $weProfiles->data();
			echo'<div class="col-12 col-sm-12 col-md-7">';
			echo '<div class="profiles_player_card '.$custom['character_class'][$cData[2]][1].'">';
				echo '<div class="profiles_player_content">';
					echo '<table class="profiles_player_table">';
						echo '<tr>';
							echo '<td class="cname">'.$cData[1].'</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td class="cclass">'.$custom['character_class'][$cData[2]][0].'</td>';
						echo '</tr>';
					echo '</table>';
					
					# info table
					echo '<table class="profiles_player_table profiles_player_table_info">';
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_7',true).'</td>';
							echo '<td>'.number_format($cData[3]).'</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_20',true).'</td>';
							echo '<td>'.number_format($cData[14]).'</td>';
						echo '</tr>';
						if(check_value($cData[4])) {
							echo '<tr>';
								echo '<td>'.lang('profiles_txt_8',true).'</td>';
								echo '<td>'.number_format($cData[4]).'</td>';
							echo '</tr>';
						}
						if(check_value($cData[11])) {
							echo '<tr>';
								echo '<td>'.lang('profiles_txt_9',true).'</td>';
								echo '<td>'.number_format($cData[11]).'</td>';
							echo '</tr>';
						}
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_10',true).'</td>';
							echo '<td>'.number_format($cData[5]).'</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_11',true).'</td>';
							echo '<td>'.number_format($cData[6]).'</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_12',true).'</td>';
							echo '<td>'.number_format($cData[7]).'</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_13',true).'</td>';
							echo '<td>'.number_format($cData[8]).'</td>';
						echo '</tr>';
						if($custom['character_class'][$cData[2]]['base_stats']['cmd'] > 0) {
							echo '<tr>';
								echo '<td>'.lang('profiles_txt_14',true).'</td>';
								echo '<td>'.number_format($cData[9]).'</td>';
							echo '</tr>';
						}
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_15',true).'</td>';
							echo '<td>'.number_format($cData[10]).'</td>';
						echo '</tr>';
						if(check_value($cData[12])) {
							echo '<tr>';
								echo '<td>'.lang('profiles_txt_16',true).'</td>';
								echo '<td>'.guildProfile($cData[12]).'</td>';
							echo '</tr>';
						}
						echo '<tr>';
							echo '<td>'.lang('profiles_txt_17',true).'</td>';
							if($cData[13]) {
								echo '<td class="isonline">'.lang('profiles_txt_18',true).'</td>';
							} else {
								echo '<td class="isoffline">'.lang('profiles_txt_19',true).'</td>';
							}
						echo '</tr>';
					echo '</table>';
				echo '</div>';
			echo '</div>';
		echo '</div>';

 $inventory = $weProfiles->GetCharInventoryResponsive($cData[1]);
                            echo "<div align='center' class='col-12 col-sm-12 col-md-5'>
									<div style='position:relative;top: 62px;color:orange;'>Inventory</div>
									" . $inventory . "
								</div>";
  
		} catch(Exception $e) {
			message('error', $e->getMessage());
		}
	} else {
		message('error', lang('error_25',true));
	}
} else {
	message('error', lang('error_47',true));
}
?>
		</div>
	</div>
</div>