{strip}
{form method="get" ipackage=search ifile="index.php"}
	<div class="row">
		<input id="fuser" name="highlight" size="20" type="text" accesskey="s" value="{tr}search{/tr}" onfocus="this.value=''" />
		<br />
		<select name="where">
			<option value="pages">{tr}Entire Site{/tr}</option>
			{if $gBitSystem->isFeatureActive( 'feature_wiki' )}
				<option value="wikis">{tr}Wiki Pages{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_articles' )}
				<option value="articles">{tr}Articles{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_blogs' )}
				<option value="blogs">{tr}Blogs{/tr}</option>
				<option value="posts">{tr}Blog Posts{/tr}</option>
			{/if}
		</select>
	</div>

	<div class="row submit">
		<input type="submit" name="search" value="{tr}go{/tr}"/>
	</div>
{/form}
{/strip}
