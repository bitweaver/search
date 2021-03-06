{strip}
<div class="display search">
	<div class="header">
		<h1>{tr}Search {if $words}Results{else}Page{/if}{/tr}</h1>
	</div>

	<div class="body">
		{form legend="Extended Search"}
			<div class="form-group">
				{formlabel label="Limit Search" for="content_type_guid"}
				{forminput}
					{html_options options=$contentTypes name=content_type_guid selected=$content_type_guid }
					{formhelp note="Limit search to the selected Liberty package"}
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Use Partial Word Search" for="usePart"}
				{forminput}
					<input type="checkbox" name="usePart" id="usePart" {if $usePart}checked{/if} />
					{formhelp note="This may slow search results"}
				{/forminput}
			</div>


			<div class="form-group">
				{formlabel label="And Terms Together" for="useAnd"}
				{forminput}
					<input type="checkbox" name="useAnd" id="useAnd" {if $useAnd}checked{/if} />
					{formhelp note="This may slow search results"}
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Find" for="find"}
				{forminput}
					<input name="highlight" size="14" id="find" type="text" accesskey="s" value="{$words|escape}"/>
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="wikiaction" name="search" value="{tr}go{/tr}"/>
			</div>
		{/form}

		{if $words}<h2>{tr}Found '<span class="highlight">{$words|escape:htmlall}</span>' in {$listInfo.total_records} {if $where2}{$where2}{else}pages{/if}{/tr}</h2>{/if}

		{section  name=search loop=$results}
			{* using capture for no particular reason appart from a nicer layout - xing *}
			{capture name=title}
				{assign var=guid value=$results[search].content_type_guid}
				<a href="{$results[search].href}&highlight={$words|escape:url}">{$results[search].title|escape}</a>
				&nbsp;
				<small>
					&bull; {tr}{$gLibertySystem->getContentTypeName($guid)}{/tr} &bull; {tr}Relevance{/tr}:&nbsp;{$results[search].relevancy} &bull; {tr}Hits{/tr}:&nbsp;{$results[search].hits}
					{if $gBitSystem->isFeatureActive( 'search_fulltext' )}
						&nbsp;&bull;&nbsp;
						{if $results[search].relevancy <= 0}
							{tr}Simple search{/tr}
						{else}
							{tr}Relevance{/tr}: {$results[search].relevancy}
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

		{pagination useAnd=$useAnd usePart=$usePart content_type_guid=$content_type_guid highlight=$words|escape }
	</div>
</div>
{/strip}
