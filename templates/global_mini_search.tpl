{strip}
{form method="get" ipackage=search ifile="index.php"}
	<div class="row">
		<input id="fuser" name="highlight" size="20" type="text" accesskey="s" value="{tr}search{/tr}" onfocus="this.value=''" />
		<br />
		<select name="where">
			{html_options options=$contentTypes selected=$perms[user].level}
		</select>
	</div>
	<div class="row submit">
		<input type="submit" name="search" value="{tr}go{/tr}"/>
	</div>
{/form}
{/strip}
