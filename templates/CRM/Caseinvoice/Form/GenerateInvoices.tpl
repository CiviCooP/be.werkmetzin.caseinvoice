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
            <td><label>{$form.activity_type_id.label}</label>
              <br />
              {$form.activity_type_id.html}
            </td>
            <td>
              {$form.status_id.label}<br/>
              {$form.status_id.html}
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
              <th scope="col">{ts}Contact{/ts}</th>
              <th scope="col">{ts}Case{/ts}</th>
              <th scope="col">{ts}Activity date{/ts}</th>
              <th scope="col">{ts}Activity type{/ts}</th>
              <th scope="col">{ts}Activity status{/ts}</th>
          </tr>


          {foreach from=$activities item=row}
            <tr id='rowid{$row.activity_id}' class="{cycle values="odd-row,even-row"} {$row.class}">
              {assign var=cbName value=$row.checkbox}
              <td>{$form.$cbName.html}</td>
              <td>{$row.display_name}</td>
              <td>{$row.case_id}</td>
              <td>{$row.activity_date_time|crmDate}</td>
              <td>{$row.activity_type_id}</td>
              <td>{$row.activity_status_id}</td>
            </tr>
          {/foreach}

        </table>
      </div>
    </div>
  </div>
{/if}