<div class="help">
  <p>Zoek op afgeronde coachingsactiviteiten en controleer of deze overeenkomen met de inkomende factuur van de coach. Zoja dan kun je daarna de activiteit op gesloten zetten.</p>
</div>

<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-advanced_search_form-accordion {if (!empty($activities))}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div>
    <!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div id="searchForm" class="form-item">
        {strip}
        <table class="form-layout">
          <tr>
            <td>
              <label>{$form.coach.label}</label>
              <br />
                {$form.coach.html}
            </td>

            <td>
              <label>{$form.client.label}</label>
              <br />
                {$form.client.html}
            </td>

          </tr>
          <tr>
            <td class="crm-case-common-form-block-case_type">
              <label>{$form.case_type_id.label}</label> <br />
              {$form.case_type_id.html}
            </td>
            <td class="crm-case-common-form-block-case_status_id">
              <label>{$form.case_status_id.label}</label> <br />
              {$form.case_status_id.html}
            </td>
          </tr>
          <tr>
            <td>
              <label>{$form.betaalwijze.label}</label>
              <br />
                {$form.betaalwijze.html}
            </td>

            <td>
            </td>

          </tr>
          <tr>
            <td><label>{ts}Activity Dates{/ts}</label></td>
          </tr>
          <tr>
            {include file="CRM/Core/DateRange.tpl" fieldName="activity_date" from='_low' to='_high'}
            <td></td>
          </tr>
          <tr>
            <td colspan="2">{include file="CRM/common/formButtons.tpl" location="botton"}</td>
          </tr>
        </table>
        {/strip}
      </div>
    </div>
  </div>
</div>

{if (!empty($activities))}
  <div class="crm-content-block">
    <div class="crm-results-block">
      {* This section handles form elements for action task select and submit *}
      <div class="crm-search-tasks">
        {include file="CRM/common/searchResultTasks.tpl"}
      </div>

      <div class="crm-search-results">
        <table class="selector row-highlight">
          <tr class="sticky">
              <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
              <th scope="col">{ts}Coach{/ts}</th>
              <th scope="col">{ts}Case{/ts}</th>
              <th scope="col">{ts}Activity type{/ts}</th>
              <th scope="col">{ts}Activity date{/ts}</th>
              <th scrope="col">{ts}Aantal{/ts}</th>
              <th scrope="col">{ts}Te factureren{/ts}</th>
              <th scrope="col">{ts}KM{/ts}</th>
          </tr>


          {foreach from=$activities item=row}
            <tr id='rowid{$row.activity_id}' class="{cycle values="odd-row,even-row"} {$row.class}">
              {assign var=cbName value=$row.checkbox}
              {assign var=contact_id value=$row.contact_id}
              {assign var=coach_id value=$row.coach_id}
              {assign var=case_id value=$row.case_id}
              {assign var=parent_case_id value=$row.parent_case_id}
              {assign var=parent_contact_id value=$row.parent_contact_id}
              <td>{$form.$cbName.html}</td>
              <td>
                <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$coach_id`"}">
                  {$row.coach_display_name}
                </a>
              </td>
              <td>
                <a href="{crmURL p='civicrm/contact/view/case' q="reset=1&action=view&id=`$case_id`&cid=`$contact_id`"}">
                    {$row.display_name} - {$row.case_type_label} ({$row.case_status_label})
                </a>
              </td>
              <td>{$row.activity_type_label}</td>
              <td>{$row.activity_date_time|crmDate}</td>
              <td>{$row.quantity}</td>
              <td>{$row.to_invoice|crmMoney}</td>
              <td>{$row.to_invoice_km|crmMoney}</td>
            </tr>
          {/foreach}

        </table>
      </div>
    </div>
  </div>
{/if}