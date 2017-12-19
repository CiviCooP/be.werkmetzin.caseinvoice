<div class="crm-block crm-form-block crm-caseinvoice-generateinvoice-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
        <h2>{ts}Test factuur{/ts}</h2>
        <p>{ts}Weet je zeker dat je voor de geselecteerde activiteiten een test factuur wilt aanmaken?{/ts}</p>
    </div>

    <table class="form-layout">
        <tr>
            <td class="crm-case-common-form-block-km">
                <label>{$form.km.label}</label> <br />
                {$form.km.html}
            </td>
        </tr>
        <tr>
            <td class="crm-case-common-form-block-payment_instrument_id">
                <label>{$form.payment_instrument_id.label}</label> <br />
                {$form.payment_instrument_id.html}
            </td>
        </tr>
        <tr>
            <td class="crm-case-common-form-block-contribution_status_id">
                <label>{$form.contribution_status_id.label}</label> <br />
                {$form.contribution_status_id.html}
            </td>
        </tr>
        <tr>
            <td class="crm-case-common-form-block-source">
                <label>{$form.source.label}</label> <br />
                {$form.source.html}
            </td>
        </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>