{strip}
{jstabs}
	{jstab title="Search Settings"}
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
			<input type="submit" name="searchaction" value="{tr}Change preferences{/tr}" />
		</div>
		{/form}
	{/jstab}

	{jstab title="Delete / Rebuild Index"}
		{form legend="Delete / Rebuild Index"}
			<input type="hidden" name="page" value="{$page}" />
			<div class="row">
				{formlabel label="Clear Searchwords:" for="clearss"}
				{forminput}
					<input type="submit" class="wikiaction" name="searchaction" value="{tr}Clear Searchwords{/tr}"/>
					{formhelp note="This clears out the cache of recently searched for terms and the syllables derived from those search terms."}
				{/forminput}
			</div>

			{formfeedback warning='Rebuilding the Index <strong>could take a long time</strong> depending on how much content you have. If this is a large site, you may want to do this during off-peak hours.'}

			<div class="row">
				{formlabel label="Perform action on:" for="where"}
				{forminput}
					<select name="where" id="where">
						<option value="pages">{tr}Entire Site{/tr}</option>
						{html_options output=$contentDescriptions values=$contentTypes}
					</select>
					{formhelp note="Limit indexing action to the selected package.<br />Note: timeout is set to 5 minutes for reindexing operations. If you need more time than this, please the command line tool search/cmd_line_reindexer.php."}
				{/forminput}
			</div>

			{formfeedback warning='Deleting the index will render search useless until content is reindexed.'}
			<div class="row submit">
				<input type="submit" name="searchaction" value="{tr}Delete Index Only{/tr}"/>
				<input type="submit" name="searchaction" value="{tr}Delete and Rebuild Index{/tr}"/>
			</div>
		{/form}
	{/jstab}
{/jstabs}
{/strip}
