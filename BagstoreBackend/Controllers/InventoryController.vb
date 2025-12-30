Imports Microsoft.AspNetCore.Mvc
Imports BagstoreBackend.Models

Namespace Controllers
    <ApiController>
    <Route("api/[controller]")>
    Public Class InventoryController
        Inherits ControllerBase

        ' GET: api/inventory
        <HttpGet>
        Public Function GetInventory() As IEnumerable(Of InventoryPart)
            ' TODO: Connect to Real Database (SQL Server)
            ' For now, return Mock Data to show on the page
            Dim mockData As New List(Of InventoryPart) From {
                New InventoryPart With {.PartNo = "P001", .Description = "ปูนซีเมนต์ถุง 50กก.", .PartFamily = "CEMENT", .MaterialType = "PAPER"},
                New InventoryPart With {.PartNo = "P002", .Description = "ปูนมอร์ต้าฉาบอิฐ", .PartFamily = "MORTAR", .MaterialType = "PAPER"},
                New InventoryPart With {.PartNo = "F001", .Description = "ปุ๋ยสูตร 15-15-15", .PartFamily = "FERTILIZER", .MaterialType = "PP"},
                New InventoryPart With {.PartNo = "F005", .Description = "ปุ๋ยยูเรีย", .PartFamily = "FERTILIZER", .MaterialType = "FILM"}
            }
            Return mockData
        End Function

    End Class
End Namespace
