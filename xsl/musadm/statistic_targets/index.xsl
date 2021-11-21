<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="root">
        <style>
            form .row div div {
                display: inline-block;
                margin: 10px 5px 10px 5px;
            }
            .table tr td span {
                vertical-align: -webkit-baseline-middle;
            }
        </style>

        <form action="plan" method="GET">
            <div class="row">
                <div class="col-md-12 text-center">
                    <div>
                        <select class="form-control" name="area">
                            <xsl:for-each select="area">
                                <option value="{id}">
                                    <xsl:if test="//area_id = id">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="title" />
                                </option>
                            </xsl:for-each>
                        </select>
                    </div>
                    <div>
                        <select class="form-control" name="month">
                            <option value="1">
                                <xsl:if test="//month = 1">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Январь
                            </option>
                            <option value="2">
                                <xsl:if test="//month = 2">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Февраль
                            </option>
                            <option value="3">
                                <xsl:if test="//month = 3">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Март
                            </option>
                            <option value="4">
                                <xsl:if test="//month = 4">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Апрель
                            </option>
                            <option value="5">
                                <xsl:if test="//month = 5">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Май
                            </option>
                            <option value="6">
                                <xsl:if test="//month = 6">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Июнь
                            </option>
                            <option value="7">
                                <xsl:if test="//month = 7">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Июль
                            </option>
                            <option value="8">
                                <xsl:if test="//month = 8">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Август
                            </option>
                            <option value="9">
                                <xsl:if test="//month = 9">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Сентябрь
                            </option>
                            <option value="10">
                                <xsl:if test="//month = 10">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Октябрь
                            </option>
                            <option value="11">
                                <xsl:if test="//month = 11">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Ноябрь
                            </option>
                            <option value="12">
                                <xsl:if test="//month = 12">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                Декабрь
                            </option>
                        </select>
                    </div>
                    <div>
                        <select class="form-control" name="year">
                            <option value="2021">
                                <xsl:if test="//year = 2021">
                                    <xsl:attribute name="selected">
                                        selected
                                    </xsl:attribute>
                                </xsl:if>
                                2021
                            </option>
                            <option value="2022">
                                <xsl:if test="//year = 2022">
                                    <xsl:attribute name="selected">
                                        selected
                                    </xsl:attribute>
                                </xsl:if>
                                2022
                            </option>
                            <option value="2023">
                                <xsl:if test="//year = 2023">
                                    <xsl:attribute name="selected">
                                        selected
                                    </xsl:attribute>
                                </xsl:if>
                                2023
                            </option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-pink">
                            Применить
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="table-responsive">
                    <table class="table table-stripped">
                        <tr>
                            <th class="text-right">Тип расходов</th>
                            <th class="text-center">Расходы<br/>(<span><xsl:value-of select="total/expenses" /></span>)</th>
                            <th>Цель<br/>(<span id="expensesTargetSpan"><xsl:value-of select="total/target" /></span>)</th>
                            <th>Действие</th>
                        </tr>
                        <xsl:apply-templates select="payment_type" />
                    </table>
                </div>
            </div>
        </div>

        <form id="creditTargetAjaxForm" method="POST" action="{wwwroot}/api/statistic_targets/index.php">
            <input type="hidden" name="action" value="set" />
            <input type="hidden" name="area_id" value="" />
            <input type="hidden" name="payment_type" value="" />
            <input type="hidden" name="month" value="{//month}" />
            <input type="hidden" name="year" value="{//year}" />
            <input type="hidden" name="target" value="" />
        </form>
    </xsl:template>

    <xsl:template match="payment_type">
        <xsl:variable name="paymentTypeId" select="id" />
        <tr>
            <td class="text-right">
                <span>
                    <xsl:value-of select="title" />
                </span>
            </td>
            <td class="text-center">
                <span>
                    <xsl:value-of select="expenses" />
                </span>
            </td>
            <td width="150px">
                <input type="number" name="target" class="form-control" value="{target}" />
            </td>
            <td>
                <a href="#" class="action save targetSave" data-area_id="{/root/area_id}" data-payment_type="{id}">
                    <input type="hidden" />
                </a>
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>