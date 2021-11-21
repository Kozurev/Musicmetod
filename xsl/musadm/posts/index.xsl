<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="root">
        <style>
            .cards-section .item {
                width: 100%;
            }

            .cards-section .item-inner {
                height: auto;
            }
        </style>

        <xsl:if test="access_post_create = 1">
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-green createPostBtn" href="#">Создать пост</a>
                </div>
            </div>
        </xsl:if>

        <div class="row">
            <div class="col-md-12">
                <ul class="pagination pagination-sm">
                    <!--Первая страница-->
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changeClientsPage(1)">Первая</a>
                    </li>

                    <!--Указатель на предыдущую страницу-->
                    <li class="page-item">
                        <xsl:if test="pagination/currentPage = 1">
                            <xsl:attribute name="class">
                                page-item disabled
                            </xsl:attribute>
                        </xsl:if>
                        <a class="page-link" href="{wwwroot}/?page={pagination/prevPage}">
                            <span aria-hidden="true">←</span>
                        </a>
                    </li>
                    <!--Предыдущая страница-->
                    <xsl:if test="pagination/prevPage != 0">
                        <li class="page-item">
                            <a class="page-link" href="{wwwroot}/?page={pagination/prevPage}">
                                <xsl:value-of select="pagination/prevPage" />
                            </a>
                        </li>
                    </xsl:if>

                    <!--Текущая страница-->
                    <li class="page-item active">
                        <a class="page-link" href="{wwwroot}/?page={pagination/currentPage}">
                            <xsl:value-of select="pagination/currentPage" />
                        </a>
                    </li>

                    <!--Следующая страница-->
                    <xsl:if test="pagination/nextPage != 0">
                        <li class="page-item">
                            <a class="page-link" href="{wwwroot}/?page={pagination/nextPage}">
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
                        <a class="page-link" href="{wwwroot}/?page={pagination/nextPage}">
                            <span aria-hidden="true">→</span>
                        </a>
                    </li>

                    <li class="page-item">
                        <a class="page-link" href="{wwwroot}/?page={pagination/countPages}">Последняя</a>
                    </li>
                </ul>
            </div>
        </div>

        <section class="cards-section">
            <div class="cards-wrapper row">
                <xsl:apply-templates select="post" />
                <input type="hidden" />
            </div>
        </section>

        <div class="modal fade" id="postsModal" tabindex="-1" role="dialog" aria-labelledby="postsLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="postsLabel">Новость</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&#215;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="posts_ajax_form" method="POST" action="{wwwroot}/api/posts/index.php?action=save">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="titleInput">Заголовок</label>
                                        <input type="text" class="form-control" id="titleInput" name="title" placeholder="Заголовок новости" required="required" />
                                    </div>
                                    <div class="form-group">
                                        <label for="contentInput">Текст новости</label>
                                        <input type="text" class="form-control" id="contentInput" name="content" required="required" />
                                    </div>
                                    <div class="form-group">
                                        <label for="areasInput">Филиалы</label>
                                        <select class="form-control selectpicker" data-live-search="true" name="areas[]" multiple="multiple">
                                            <xsl:apply-templates select="area" />
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отменить</button>
                        <button type="button" class="btn btn-primary" onclick="tinymce.get('contentInput').save(); $('#posts_ajax_form').submit(); return false;">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="post">
        <div class="col-md-12">
            <div class="item item-orange post">
                <div class="item-inner">
                    <div class="row">
                        <div class="col-sm-8 col-xs-12">
                            <h3 class="title">
                                <span class="title">
                                    <xsl:value-of select="title" />
                                </span>
                            </h3>
                        </div>
                        <div class="col-sm-4 col-xs-12 text-right">
                            <span class="post_author_name"><xsl:value-of select="author_name" /></span>
                            <span><xsl:text> </xsl:text></span>
                            <span class="post_date"><xsl:value-of select="refactored_date" /></span>
                        </div>
                        <div class="col-md-12">
                            <span class="post_content"><xsl:value-of disable-output-escaping="yes" select="content" /></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="area">
        <option value="{id}"><xsl:value-of select="title" /></option>
    </xsl:template>

</xsl:stylesheet>