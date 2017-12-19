<?php

class CRM_Caseinvoice_Form_Task_GenerateTestInvoice extends CRM_Caseinvoice_Form_Task_GenerateInvoice {
	
	protected function alterContributionParameters(&$contributionParameters) {
		$contributionParameters['is_test'] = 1;
	}
	
}
