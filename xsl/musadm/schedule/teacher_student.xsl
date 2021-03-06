<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template name="teacher_student">
        <div class="col-lg-6 col-sm-12 col-xs-12">
            <h3>Список учеников преподавателя</h3>
            <div class="table-responsive">
                <table class="table table-bordered teacher-schedule-main" id="student_table">
                    <tr>
                        <td>
                            <span>Фамилия</span>
                        </td>
                        <td>
                            <span>Имя</span>
                        </td>
                        <td>
                            <span>Телефон</span>
                        </td>
                            <td>

                                <a class="btn btn-green" onclick="addNewStudentToTeacher({//root/value_id})"> + </a>
                            </td>
                    </tr>
                    <xsl:apply-templates select="clients"/>
                </table>
            </div>
        </div>
    </xsl:template>
    <xsl:template match="clients">
        <tr id="{id}">
            <td><xsl:value-of select="surname"/></td>
            <td><xsl:value-of select="name"/></td>
            <td><xsl:value-of select="phone_number"/></td>
                 <td>
                     <a class="btn btn-red" id="del_student" onclick="deleteProperty('teachers','User',{id},delTeachersStudentCallback)"> - </a>
                 </td>
        </tr>
    </xsl:template>
</xsl:stylesheet>