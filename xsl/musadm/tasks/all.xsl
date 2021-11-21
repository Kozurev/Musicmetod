<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:include href="task.xsl" />
    <xsl:include href="areas_select.xsl" />

    <xsl:template match="root">

        <input type='hidden' id='taskAfterAction' value='{taskAfterAction}' />

        <section class="section-bordered center">
            <xsl:if test="periods = 1">
                <div class="row finances-calendar">
                    <div class="right">
                        <h4>Период с:</h4>
                    </div>

                    <div>
                        <input type="date" class="form-control" name="date_from" value="{date_from}"/>
                    </div>

                    <div class="right">
                        <h4>по:</h4>
                    </div>

                    <div>
                        <input type="date" class="form-control" name="date_to" value="{date_to}"/>
                    </div>

                    <div>
                        <h4>№</h4>
                    </div>

                    <div>
                        <input class="form-control" name="task_id" value="{task_id}" style="max-width: 90px" />
                    </div>

                    <div>
                        <a class="btn btn-red"
                           onclick="refreshTasksTable(
                                $('input[name=date_from]').val(),
                                $('input[name=date_to]').val(),
                                $('select[name=area_id]').val(),
                                $('input[name=task_id]').val()
                            )">
                            Показать
                        </a>
                    </div>
                </div>
            </xsl:if>

            <xsl:if test="buttons-panel = 1">
                <div class="row buttons-panel">
                    <xsl:call-template name="areas_row" />

                    <div style="display: inline-block;width: 200px;">
                        <input class="checkbox" id="only_system_tasks" name="only_system_tasks" type="checkbox">
                            <xsl:if test="//only_system = 1">
                                <xsl:attribute name="checked">true</xsl:attribute>
                            </xsl:if>
                        </input>
                        <label for="only_system_tasks" class="checkbox-label" style="position:relative;top:20px;">
                            <span class="off">Все</span>
                            <span class="on">Системные</span>
                        </label>
                    </div>

                    <xsl:choose>
                        <xsl:when test="periods = 1">
                            <xsl:if test="access_task_create = 1">
                                <div>
                                    <a class="btn btn-red" onclick="newTaskPopup(0, 'refreshTasksTable')">Добавить задачу</a>
                                </div>
                            </xsl:if>
                        </xsl:when>
                        <xsl:otherwise>
                            <div>
                                <a class="btn btn-red" onclick="newTaskPopup(0, 'refreshTasksTable')">Добавить задачу</a>
                            </div>
                        </xsl:otherwise>
                    </xsl:choose>

                    <span>Незавершенные задачи/всего: <xsl:value-of select="count(//task[done_date=''])" />/<xsl:value-of select="count(//task)" /></span>
                </div>
            </xsl:if>
        </section>

        <section class="cards-section text-center tasks-section">
            <div id="cards-wrapper" class="cards-wrapper row">
                <xsl:apply-templates select="task[done = 0]" />
                <xsl:if test="show_completed = 1">
                    <xsl:apply-templates select="task[done = 1]" />
                </xsl:if>
            </div>
        </section>
        <div class="center">
            <a class="btn btn-red" onclick="refreshTasksTable(
            $('input[name=date_from]').val(),
            $('input[name=date_to]').val(),
            $('select[name=area_id]').val(),
            $('input[name=task_id]').val(),
            true, )">
                Показать завершенные
            </a>
        </div>
    </xsl:template>





</xsl:stylesheet>