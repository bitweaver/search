{strip}
<div class="display search">
	<div class="header">
		<h1>{tr}Search results{/tr}</h1>
	</div>

	<div class="body">
		{form legend="Extended Search"}
			<div class="row">
				{formlabel label="Limit Search" for="where"}
				{forminput}
					<select name="where" id="where">
						<option value="pages">{tr}Entire Site{/tr}</option>
						{html_options output=$contentTypes values=$contentTypes}
					</select>
					{formhelp note="Limit search to the selected Liberty package"}
				{/forminput}
			</div>

			<div class="row">
				{formlabel label="Find" for="find"}
				{forminput}
					<input name="highlight" size="14" id="find" type="text" accesskey="s" value="{$words|escape}"/>
				{/forminput}
			</div>

			<div class="row submit">
				<input type="submit" class="wikiaction" name="search" value="{tr}go{/tr}"/>
			</div>
		{/form}

		{if $words}<h2>{tr}Found '<span class="highlight">{$words}</span>' in {$cant_results} {if $where2}{$where2}{else}pages{/if}{/tr}</h2>{/if}

		{section  name=search loop=$results}
			{* using capture for no particular reason appart from a nicer layout - xing *}
			{capture name=title}
				{assign var=guid value=$results[search].location}
				{tr}{$gLibertySystem->mContentTypes.$guid.content_description}{/tr} <a href="{$results[search].href}&highlight={$words}">{$results[search].title}</a>
				<small> &bull;&nbsp;{tr}Hits{/tr}: {$results[search].hits}
					{if $gBitSystem->isFeatureActive( 'feature_search_fulltext' )}
						&nbsp;&bull;&nbsp;
						{if $results[search].relevance <= 0}
							{tr}Simple search{/tr}
						{else}
							{tr}Relevance{/tr}: {$results[search].relevance}
						{/if}
					{/if}
					{if $results[search].type > ''}
						&nbsp; ( {$results[search].type} )
					{/if}
				</small>
			{/capture}

			<div class="search box">
				<h3>{$smarty.capture.title}</h3>
				<div class="boxcontent">
					{$results[search].parsed|strip_tags|truncate:250}
					<br />
					<span class="date">{tr}Last modification{/tr}: {$results[search].last_modified|bit_long_datetime}</span>
				</div>
			</div>
		{sectionelse}
			{if $words}<div class="norecords">{tr}No pages matched the search criteria{/tr}</div>{/if}
		{/section}

		{pagination}
	</div>
</div>
{/strip}
