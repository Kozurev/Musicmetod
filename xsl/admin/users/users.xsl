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

			<xsl:if test="count(user_group/id) != 0">
				<table class="table">
					<th>id</th>
					<th>Название</th>
					<th>Путь</th>
					<th>Редактировать</th>
					<th>Удалить</th>
					<xsl:apply-templates select="user_group" />
				</table>
			</xsl:if>

			<xsl:if test="count(user/id) != 0">
				<table class="table">
					<th>id</th>
					<th>Логин</th>
					<th>Фамилия</th>
					<th>Имя</th>
					<th>Активность</th>
					<th>Редактировать</th>
					<th>Удалить</th>
					<xsl:apply-templates select="user" />
				</table>
			</xsl:if>

			<xsl:choose>
				<xsl:when test="count(user/id) = 0">
					<button class="btn button" type="button">
						<a href="admin?menuTab=Main&amp;menuAction=updateForm&amp;model=User_Group&amp;parent_id={parent_id}" class="link">
							Создать группу
						</a>
					</button>
				</xsl:when>
				<xsl:otherwise>
					<button class="btn button" type="button">
						<a href="admin?menuTab=User&amp;menuAction=updateForm&amp;model=User&amp;parent_id={parent_id}" class="link">
							Создать пользователя
						</a>
					</button>
				</xsl:otherwise>
			</xsl:choose>

			<div class="pagination">
				<a class="prev_page" href="admin?menuTab=User&amp;action=show&amp;group_id={group_id}"></a>
				<span class="pages">Страница
					<span id="current_page"><xsl:value-of select="pagination/current_page" /></span> из
					<span id="count_pages"><xsl:value-of select="pagination/count_pages" /></span></span>
				<a class="next_page" href="admin?menuTab=User&amp;action=show&amp;group_id={group_id}"></a>
				<span class="total_count">Всего элементов: <xsl:value-of select="pagination/total_count"/></span>
			</div>

		</div>
	</xsl:template>


	<xsl:template match="user_group">
		<tr>
			<td><xsl:value-of select="id"/></td>

			<td class="table_structure">
				<a class="link" href="admin?menuTab=User&amp;menuAction=show&amp;group_id={id}">
					<xsl:value-of select="title" />
				</a>
			</td>

			<td><xsl:value-of select="path" /></td>

			<!--Редактирование-->
			<td><a href="admin?menuTab=Main&amp;menuAction=updateForm&amp;model=User_Group&amp;model_id={id}" class="link updateLink" /></td>

			<!--Удаление-->
			<td><a href="admin" data-model_name="User_Group" data-model_id="{id}" class="delete deleteLink"></a></td>
		</tr>
	</xsl:template>


	<xsl:template match="user">
		<tr>
			<td><xsl:value-of select="id" /></td>
			<td><xsl:value-of select="login" /></td>
			<td><xsl:value-of select="surname" /></td>
			<td><xsl:value-of select="name" /></td>

			<!--Активность-->
			<td>
				<input type="checkbox" class="activeCheckbox">
					<xsl:attribute name="model_name">User</xsl:attribute>
					<xsl:attribute name="model_id"><xsl:value-of select="id" /></xsl:attribute>
					<xsl:if test="active = 1">
						<xsl:attribute name="checked">true</xsl:attribute>
					</xsl:if>
				</input>
			</td>

			<!--Редактирование-->
			<td><a href="admin?menuTab=Main&amp;menuAction=updateForm&amp;model=User&amp;model_id={id}" class="link updateLink" /></td>

			<!--Удаление-->
			<td><a href="admin" data-model_name="User" data-model_id="{id}" class="delete deleteLink"></a></td>
		</tr>
	</xsl:template>


</xsl:stylesheet>