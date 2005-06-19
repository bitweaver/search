{strip}
{form method="get" ipackage=search ifile="index.php"}
	<div class="row">
		<input id="fuser" name="highlight" size="14" type="text" accesskey="s" value="{tr}search{/tr}" onfocus="this.value=''" />
		<select name="where">
			<option value="pages">{tr}Entire Site{/tr}</option>
			{if $gBitSystem->isFeatureActive( 'feature_wiki' )}
				<option value="wikis">{tr}Wiki Pages{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_directory' )}
				<option value="directory">{tr}Directory{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_galleries' )}
				<option value="galleries">{tr}Image Gals{/tr}</option>
				<option value="images">{tr}Images{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_file_galleries' )}
				<option value="files">{tr}Files{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_articles' )}
				<option value="articles">{tr}Articles{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_tiki_forums' )}
				<option value="forums">{tr}Forums{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_blogs' )}
				<option value="blogs">{tr}Blogs{/tr}</option>
				<option value="posts">{tr}Blog Posts{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_faqs' )}
				<option value="faqs">{tr}FAQs{/tr}</option>
			{/if}
			{if $gBitSystem->isFeatureActive( 'feature_trackers' )}
				<option value="trackers">{tr}Tracker{/tr}</option>
			{/if}
		</select>
	</div>

	<div class="row submit">
		<input type="submit" name="search" value="{tr}go{/tr}"/>
	</div>
{/form}
{/strip}
