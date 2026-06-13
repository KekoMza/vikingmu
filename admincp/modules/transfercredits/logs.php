<?php
/**
 * Transfer Credits
 * https://webenginecms.org/
 * 
 * @version 1.0.0
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2019 Lautaro Angelico, All Rights Reserved
 * @build w3c8c718b75a0f1fa1a557f7f9d70877
 */

$TransferCredits = new \Plugin\TransferCredits\TransferCredits();
$pendingTransfers = $TransferCredits->getPendingTransfers();
$completedTransfers = $TransferCredits->getCompletedTransfers();

echo '<h2>Pending Transfers:</h2>';
if(is_array($pendingTransfers)) {
	echo '<table class="table table-striped table-condensed table-hover">';
		echo '<tr>';
			echo '<th>Transfer Id</th>';
			echo '<th>Transfer Date</th>';
			echo '<th>Sent By</th>';
			echo '<th>Sent To</th>';
			echo '<th>Credits Transferred</th>';
		echo '</tr>';
		foreach($pendingTransfers as $row) {
			echo '<tr>';
				echo '<td>'.$row['id'].'</td>';
				echo '<td>'.$row['date_sent'].'</td>';
				echo '<td>'.$row['sent_by'].'</td>';
				echo '<td>'.$row['sent_to'].'</td>';
				echo '<td>'.$row['amount'].' '.$row['credits_title'].'</td>';
			echo '</tr>';
		}
	echo '</table>';
} else {
	message('warning', 'There are no pending transfers.');
}

echo '<h2>Completed Transfers:</h2>';
if(is_array($completedTransfers)) {
	echo '<table class="table table-striped table-condensed table-hover">';
		echo '<tr>';
			echo '<th>Transfer Id</th>';
			echo '<th>Transfer Date</th>';
			echo '<th>Sent By</th>';
			echo '<th>Sent To</th>';
			echo '<th>Date Received</th>';
			echo '<th>Credits Transferred</th>';
		echo '</tr>';
		foreach($completedTransfers as $row) {
			echo '<tr>';
				echo '<td>'.$row['id'].'</td>';
				echo '<td>'.$row['date_sent'].'</td>';
				echo '<td>'.$row['sent_by'].'</td>';
				echo '<td>'.$row['sent_to'].'</td>';
				echo '<td>'.$row['date_received'].'</td>';
				echo '<td>'.$row['amount'].' '.$row['credits_title'].'</td>';
			echo '</tr>';
		}
	echo '</table>';
} else {
	message('warning', 'There are no completed transfers.');
}