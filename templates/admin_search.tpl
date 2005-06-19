{strip}
{form legend="Search Settings"}
	<input type="hidden" name="page" value="{$page}" />

	{foreach from=$formSearchToggles key=item item=output}
		<div class="row">
			{formlabel label=`$output.label` for=$item}
			{forminput}
				{html_checkboxes name="$item" values="y" checked=`$gBitSystemPrefs.$item` labels=false id=$item}
				{formhelp note=`$output.note` page=`$output.page`}
			{/forminput}
		</div>
	{/foreach}

	{foreach from=$formSearchInts key=item item=output}
		<div class="row">
			{formlabel label=`$output.label` for=$item}
			{forminput}
				<input size="5" type="text" name="{$item}" id="{$item}" value="{$output.value|escape}" />
				{formhelp note=`$output.note` page=`$output.page`}
			{/forminput}
		</div>
	{/foreach}

	<div class="row submit">
		<input type="submit" name="searchprefs" value="{tr}Change preferences{/tr}" />
	</div>

{/form}
{/strip}
