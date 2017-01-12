<div class="crm-block crm-form-block crm-caseinvoice-generateinvoice-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr>
            <td class="crm-case-common-form-block-status_id">
                <label>{$form.status_id.label}</label> <br />
                {$form.status_id.html}
            </td>
        </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>