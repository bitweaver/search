{strip}
{jstabs}
	{jstab title="Search Settings"}
		{formfeedback hash=$feedback}

		{form legend="Search Settings"}
			<input type="hidden" name="page" value="{$page}" />

			{foreach from=$formSearchToggles key=item item=output}
				<div class="control-group column-group gutters">
					{formlabel label=$output.label for=$item}
					{forminput}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
						{formhelp note=$output.note page=$output.page}
					{/forminput}
				</div>
			{/foreach}

			{foreach from=$formSearchInts key=item item=output}
				<div class="control-group column-group gutters">
					{formlabel label=$output.label for=$item}
					{forminput}
						<input size="5" type="text" name="{$item}" id="{$item}" value="{$output.value|escape}" />
						{formhelp note=$output.note page=$output.page}
					{/forminput}
				</div>
			{/foreach}

			<div class="row submit">
				<input type="submit" name="store_prefs" value="{tr}Change preferences{/tr}" />
			</div>
		{/form}
	{/jstab}

	{jstab title="Searchable Content"}
		{form legend="Searchable Content"}
			{foreach from=$formSearchTypeToggles key=item item=output}
				<div class="control-group column-group gutters">
					{formlabel label=$output.label for=$item}
					{forminput}
						{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item}
						{formhelp note=$output.note page=$output.page}
					{/forminput}
				</div>
			{/foreach}

			<input type="hidden" name="page" value="{$page}" />
			<div class="control-group column-group gutters">
				{formlabel label="Searchable Content"}
				{forminput}
					{html_checkboxes options=$formSearchable.guids value=y name=searchable_content separator="<br />" checked=$formSearchable.checked}
					{formhelp note="Here you can select what content can be searched."}
				{/forminput}
			</div>

			<div class="control-group submit">
				<input type="submit" name="store_content" value="{tr}Change preferences{/tr}" />
			</div>
		{/form}

	{/jstab}

	{jstab title="Delete / Rebuild Index"}
		{formfeedback hash=$feedback}

		{form legend="Delete / Rebuild Index"}
			<input type="hidden" name="page" value="{$page}" />
			<div class="control-group column-group gutters">
				{formlabel label="Clear Searchwords:" for="clearss"}
				{forminput}
					<input type="submit" name="del_searchwords" value="{tr}Clear Searchwords{/tr}"/>
					{formhelp note="This clears out the cache of recently searched for terms and the syllables derived from those search terms."}
				{/forminput}
			</div>
			<div class="control-group column-group gutters">
				{formfeedback warning='Deleting the index will render search useless until content is reindexed.'}
				{formfeedback warning='Rebuilding the Index <strong>could take a long time</strong> depending on how much content you have. If this is a large site, you may want to do this during off-peak hours.'}
				{formfeedback warning='Note: timeout setting will automatically be set to 5 minutes for reindexing operations.'}
			</div>
			<div class="control-group column-group gutters">
				{formlabel label="Perform action on:" for="where"}
				{forminput}
					<select name="where" id="where">
						{html_options options=$contentTypes}
					</select>
					{formhelp note="Limit indexing action to the selected Liberty package"}
				{/forminput}
			</div>
			<div class="control-group submit">
				<input type="submit" name="del_index" value="{tr}Delete Index Only{/tr}"/>
				&nbsp;&nbsp;
				<input type="submit" name="del_index_reindex" value="{tr}Delete and Rebuild Index{/tr}"/>
			</div>
		{/form}
	{/jstab}
{/jstabs}
{/strip}
