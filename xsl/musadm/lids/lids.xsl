<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:include href="areas_select.xsl"/>
    <xsl:include href="lid_card.xsl" />

    <xsl:template match="root">
        <section class="section-bordered">
            <xsl:if test="periods = 1">
                <form name="filter_lids" id="filter_lids">
                    <div class="row finances-calendar">
                        <div class="right">
                            <h4>Период с:</h4>
                        </div>

                        <div>
                            <input type="date" class="form-control" name="date_from" value="{//date_from}"/>
                        </div>

                        <div class="right">
                            <h4>по:</h4>
                        </div>

                        <div>
                            <input type="date" class="form-control" name="date_to" value="{//date_to}"/>
                        </div>

                        <xsl:call-template name="areas_row" />
                    </div>
                    <div class="row buttons-panel center">
                        <div>
                            <input class="form-control" type="number" name="id" placeholder="Номер лида" value="{/root/id}"/>
                        </div>
                        <div>
                            <input class="form-control" type="text" name="number" placeholder="Телефон" value="{/root/number}"/>
                        </div>
                        <div>
                            <input class="form-control" type="text" name="vk" placeholder="Ссылка vk" value="{/root/vk}"/>
                        </div>
                        <div>
                            <select name="status_id" class="form-control">
                                <option value="0">Статус</option>
                                <xsl:for-each select="lid_status">
                                    <xsl:variable name="id" select="id" />
                                    <option value="{id}">
                                        <xsl:if test="$id = /root/status_id">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="title" />
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>

                        <div>
                            <select name="instrument" class="form-control">
                                <option value="0">Инструмент</option>
                                <xsl:for-each select="//property[tag_name='instrument']/values/property_list_values">
                                    <xsl:variable name="id" select="id" />
                                    <option value="{id}">
                                        <xsl:if test="$id = /root/instrument">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="value" />
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>
                        <div>
                            <select name="source" class="form-control">
                                <option value="0">Источник</option>
                                <xsl:for-each select="//property[tag_name='lid_source']/values/property_list_values">
                                    <xsl:variable name="id" select="id" />
                                    <option value="{id}">
                                        <xsl:if test="$id = /root/source">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="value" />
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>
                    </div>
                    <div class="row buttons-panel center">
                        <div>
                            <a class="btn btn-purple lids_search">Показать</a>
                        </div>

                        <xsl:if test="//access_lid_create = 1">
                            <div>
                                <a class="btn btn-purple" onclick="makeLidPopup(0)">Создать лида</a>
                            </div>
                        </xsl:if>

                        <div>
                            <span>Количество надйеных лидов: </span><xsl:value-of select="countLids" />
                        </div>
                    </div>
                </form>
            </xsl:if>
        </section>

        <section class="cards-section section-lids text-center">
            <ul class="pagination pagination-sm">
                <!--Первая страница-->
                <li class="page-item">
                    <a class="page-link" href="#" onclick="refreshLidTable(1)">Первая</a>
                </li>

                <!--Указатель на предыдущую страницу-->
                <li class="page-item">
                    <xsl:if test="pagination/currentPage = 1">
                        <xsl:attribute name="class">
                            page-item disabled
                        </xsl:attribute>
                    </xsl:if>
                    <a class="page-link" href="#" onclick="refreshLidTable({pagination/prevPage})">
                        <span aria-hidden="true">←</span>
                    </a>
                </li>
                <!--Предыдущая страница-->
                <xsl:if test="pagination/prevPage != 0">
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="refreshLidTable({pagination/prevPage})">
                            <xsl:value-of select="pagination/prevPage" />
                        </a>
                    </li>
                </xsl:if>

                <!--Текущая страница-->
                <li class="page-item active">
                    <a class="page-link" href="#" onclick="refreshLidTable({pagination/currentPage})">
                        <xsl:value-of select="pagination/currentPage" />
                    </a>
                </li>

                <!--Следующая страница-->
                <xsl:if test="pagination/nextPage != 0">
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="refreshLidTable({pagination/nextPage})">
                            <xsl:value-of select="pagination/nextPage" />
                        </a>
                    </li>
                </xsl:if>

                <!--Указатель на следующую страницу-->
                <li class="page-item">
                    <xsl:if test="pagination/currentPage = pagination/countPages">
                        <xsl:attribute name="class">
                            page-item disabled
                        </xsl:attribute>
                    </xsl:if>
                    <a class="page-link" href="#" onclick="refreshLidTable({pagination/nextPage})">
                        <span aria-hidden="true">→</span>
                    </a>
                </li>

                <li class="page-item">
                    <a class="page-link" href="#" onclick="refreshLidTable({pagination/countPages})">Последняя</a>
                </li>
            </ul>
            <ul class="pagination pagination-sm">
                <li class="page-item">
                    <xsl:choose>
                        <xsl:when test="(paginate)">
                            <a class="page-link" href="?notPaginate">Показать всех</a>
                        </xsl:when>
                        <xsl:otherwise>
                            <a class="page-link" href="?paginate">Скрыть всех</a>
                        </xsl:otherwise>
                    </xsl:choose>
                </li>
            </ul>
            <div id="cards-wrapper" class="cards-wrapper row">
                <xsl:choose>
                    <xsl:when test="count(lid) != 0">
                        <xsl:apply-templates select="lid" />
                    </xsl:when>
                    <xsl:otherwise>
                        <h2 class="section-title">Ничего не найдено</h2>
                    </xsl:otherwise>
                </xsl:choose>
            </div>
        </section>
    </xsl:template>



</xsl:stylesheet>