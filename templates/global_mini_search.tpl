{strip}
{form method="get" ipackage=search ifile="index.php"}
	<div class="form-group">
		<input id="fuser" name="highlight" size="20" type="text" accesskey="s" value="{tr}search{/tr}" onfocus="this.value=''" />
		<br />
		{html_options options=$contentTypes name="content_type_guid" selected=$perms[user].level}
	</div>
	<div class="form-group submit">
		<input type="submit" name="search" value="{tr}go{/tr}" />
	</div>
{/form}
{/strip}
