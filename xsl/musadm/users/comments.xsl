<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="root">
        <section class="user-comments">
            <xsl:if test="access_user_append_comment = 1">
                <div class="row">
                    <div class="col-md-3 col-xs-12 right">
                        <h4>Добавить комментарий</h4>
                    </div>
                    <div class="col-md-7 col-xs-12 left">
                        <input class="form-control" id="user_comment" placeholder="Комментарий" />
                    </div>
                    <div class="col-md-2 col-xs-6">
                        <a class="btn btn-orange" id="user_teacher_comment_save" href="#" data-userid="{user/id}">Сохранить</a>
                    </div>
                </div>
            </xsl:if>
            <xsl:choose>
                <xsl:when test="count(comment)=0">
                    <h5 class="text-center">Комментариев не найдено</h5>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:apply-templates select="comment" />
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates select="event" />
            <input type="hidden" />
        </section>
    </xsl:template>

    <xsl:template match="comment">
        <div class="block">
            <div class="comment_header">
                <div class="author">
                    <xsl:value-of select="author_fullname" />
                </div>
                <div class="date">
                    <xsl:value-of select="refactored_date" />
                </div>
            </div>
            <div class="comment_body">
                <p><xsl:value-of select="text" disable-output-escaping="yes" /></p>
            </div>
        </div>
    </xsl:template>

</xsl:stylesheet>