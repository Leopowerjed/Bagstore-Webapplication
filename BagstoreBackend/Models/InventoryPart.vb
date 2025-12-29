Namespace Models
    Public Class InventoryPart
        ''' <summary>
        ''' รหัสสินค้า (Primary Key)
        ''' </summary>
        Public Property PartNo As String

        ''' <summary>
        ''' รายละเอียด
        ''' </summary>
        Public Property Description As String

        ''' <summary>
        ''' CEMENT / MORTAR / FERTILIZER
        ''' </summary>
        Public Property PartFamily As String

        ''' <summary>
        ''' PAPER / PP / FILM
        ''' </summary>
        Public Property MaterialType As String
    End Class
End Namespace
