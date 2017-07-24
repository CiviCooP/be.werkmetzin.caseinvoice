{if $outputMode neq 'print'}
{/if}
{* this div is being used to apply special css *}
{if $section eq 1}
    <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    </div>
{elseif $section eq 2}
    <div class="crm-block crm-content-block crm-report-layoutTable-form-block">
        {*include the table layout*}
        {include file="CRM/Report/Form/Layout/Table.tpl"}
    </div>
{else}
    {if $criteriaForm OR $instanceForm OR $instanceFormError}
        <div class="crm-block crm-form-block crm-report-field-form-block">
            {include file="CRM/Report/Form/Fields.tpl"}
        </div>
    {/if}

    <div class="crm-block crm-content-block crm-report-form-block">
        {*include actions*}
        {include file="CRM/Casereports/Form/Report/FixedPriceActiviteitenSpecificatie/Actions.tpl"}

        {*Statistics at the Top of the page*}
        {if $outputMode eq 'html'}
        {include file="CRM/Report/Form/Statistics.tpl" top=true}
        {/if}

        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}

        {*include the table layout*}
        {include file="CRM/Casereports/Form/Report/FixedPriceActiviteitenSpecificatie/Table.tpl"}
        <br />
        {*Statistics at the bottom of the page*}
        {if $outputMode eq 'html'}
        {include file="CRM/Report/Form/Statistics.tpl" bottom=true}
        {/if}

        {include file="CRM/Report/Form/ErrorMessage.tpl"}
    </div>
{/if}
{if $outputMode == 'print'}
    <script type="text/javascript">
        window.print();
    </script>
{/if}