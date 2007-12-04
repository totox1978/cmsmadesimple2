{validation_errors for=$stylesheet_object}
{$header_name}
<form method="post" name="cssform" id="cssform" action="{$action}">
	<div id="page_tabs">
		<ul>
			<li><a href="#content"><span>Content</span></a></li>
			<li><a href="#advanced"><span>Advanced</span></a></li>
		</ul>
	    <div id="content">
			{admin_input type='input' label='name' id='css_name' name='stylesheet[name]' value=$stylesheet_object->name}
			{admin_input type='textarea' label='content' id='css_text' name='stylesheet[value]' value=$stylesheet_object->value}
		</div>
		<div id="advanced">
			<h3>{lang string='mediatype'}</h3>
			{foreach from=$media_types item='type'}
				<div class="row">
					{if isset($type.selected)}
						{html_checkbox id=$type.name name='media_types[]' selected=true}
					{else}
						{html_checkbox id=$type.name name='media_types[]'}
					{/if}
					{capture assign='lang_key'}mediatype_{$type.name}{/capture}
					<label for="{$type.name}" style="white-space:nowrap;margin-left:10px;">{lang string=$lang_key}</label>
				</div>
			{/foreach}
		</div>
	</div>
	<div class="submitrow">
		<input type="hidden" name="addcss" value="true" />
		<input type="submit" name="submitbutton" value="{lang string='submit'}" class="pagebutton" onmouseover="this.className='pagebuttonhover'" onmouseout="this.className='pagebutton'" />
		<input type="submit" name="cancel" value="{lang string='cancel'}" class="pagebutton" onmouseover="this.className='pagebuttonhover'" onmouseout="this.className='pagebutton'" />
	</div>	
</form>

<script type="text/javascript">
<!--
	$('#page_tabs').tabs({$start_tab});
//-->
</script>