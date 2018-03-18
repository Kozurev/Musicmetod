<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output xmlns="http://www.w3.org/TR/xhtml1/strict" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" encoding="utf-8" indent="yes" method="html" omit-xml-declaration="no" version="1.0" media-type="text/xml"/>


	<xsl:template match="root">
		<div class="in_main">
			<h3 class="main_title">
				<xsl:value-of select="title" />
			</h3>
			<table class="table">
				<th>id</th>
				<th>Заголовок</th>
				<th>Название</th>
				<th>Описание</th>
				<th>Активность</th>
				<th>Редактировать</th>
				<th>Удалить</th>
				<xsl:apply-templates select="constant_dir" />
				<xsl:apply-templates select="constant" />
			</table>

			<button class="btn btn-success" type="button">
				<a 
					href="/admin?menuTab=Main&amp;menuAction=updateForm&amp;model=Constant_Dir&amp;parent_id={parent_id}" 
					class="link">
					Создать раздел
				</a>
			</button>

			<button class="btn btn-success" type="button">
				<a 
					href="/admin?menuTab=Main&amp;menuAction=updateForm&amp;model=Constant&amp;parent_id={parent_id}" 
					class="link">
					Создать константы
				</a>
			</button>
		</div>


	</xsl:template>


	<xsl:template match="constant_dir">
		<tr>
			<!--id-->
			<td><xsl:value-of select="id" /></td>

			<!--Заголовок-->
			<td class="table_structure">
				<a 
					class="link"
					href="/admin?menuTab=Constant&amp;
					menuAction=show&amp;parent_id={id}">
					<xsl:value-of select="title" />
				</a>
			</td>

			<!--Название-->
			<td></td>

			<!--Описание-->
			<td><xsl:value-of select="description" /></td>

			<!--Активность-->
			<td></td>

			<!--Редактирование-->
			<td>
				<a 
					href="/admin?menuTab=Main&amp;menuAction=updateForm&amp;model=Constant_Dir&amp;parent_id={parent_id}&amp;model_id={id}"
					class="link">
					<img 
						src="/templates/template3/images/delete.ico" 
						class="delete_icon"/>
					</a>
			</td>

			<!--Удаление-->
			<td>
				<a 
					href="/admin" data-model_name="Constant_Dir" data-model_id="{id}" class="delete">
					<img 
						src="/templates/template3/images/delete.ico" 
						class="delete_icon"/>
					</a>
			</td>
		</tr>
	</xsl:template>


	<xsl:template match="constant">
		<tr>
			<!--id-->
			<td><xsl:value-of select="id" /></td>
			
			<!--Заголовок-->
			<td><xsl:value-of select="title" /></td>
			
			<!--Название-->
			<td><xsl:value-of select="name" /></td>

			<!--Описание-->
			<td><xsl:value-of select="description" /></td>

			<!--Активность-->
			<td>
				<input type="checkbox" class="activeCheckbox">
					<xsl:attribute name="model_name">Constant</xsl:attribute>
					<xsl:attribute name="model_id"><xsl:value-of select="id" /></xsl:attribute>
					<xsl:if test="active = 1">
						<xsl:attribute name="checked">true</xsl:attribute>
					</xsl:if>
				</input>
			</td>

			<!--Редактирование-->
			<td>
				<a 
					href="/admin?menuTab=Main&amp;menuAction=updateForm&amp;model=Constant&amp;parent_id={parent_id}&amp;model_id={id}"
					class="link">
					<img 
						src="/templates/template3/images/delete.ico" 
						class="delete_icon"/>
				</a>
			</td>

			<!--Удаление-->
			<td>
				<a href="/admin" data-model_name="Constant" data-model_id="{id}" class="delete">
					<img 
						src="/templates/template3/images/delete.ico" 
						class="delete_icon"/>
				</a>
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>