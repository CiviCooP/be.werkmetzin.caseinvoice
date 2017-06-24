<div class="crm-block crm-form-block">
    {* HEADER *}
    <h2>{ts}Facturatieinstellingen{/ts}</h2>
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <div class="help-block" id="help">
        {ts}Geef hier de facturatieinstellingen op die systeembreed gelden, zoals welke activiteiten coachingsactiviteiten zijn, welke ondersteuning, de km vergoeding etc.{/ts}
    </div>
    <div class="crm-section">
        <div class="label">{$form.coachings_activity_type_ids.label}</div>
        <div class="content">{$form.coachings_activity_type_ids.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.ondersteunings_activity_type_ids.label}</div>
        <div class="content">{$form.ondersteunings_activity_type_ids.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.activity_status_id.label}</div>
        <div class="content">{$form.activity_status_id.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.km.label}</div>
        <div class="content">{$form.km.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.payment_instrument_id.label}</div>
        <div class="content">{$form.payment_instrument_id.html}</div>
        <div class="clear"></div>
    </div>
    <div class="crm-section">
        <div class="label">{$form.contribution_status_id.label}</div>
        <div class="content">{$form.contribution_status_id.html}</div>
        <div class="clear"></div>
    </div>
    {* FOOTER *}
    <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>