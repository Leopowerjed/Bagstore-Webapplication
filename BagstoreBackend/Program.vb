Imports Microsoft.AspNetCore.Builder
Imports Microsoft.Extensions.DependencyInjection
Imports Microsoft.Extensions.Hosting

Module Program
    Sub Main(args As String())
        Dim builder = WebApplication.CreateBuilder(args)

        ' Add services to the container.
        builder.Services.AddControllers()

        Dim app = builder.Build()

        ' Configure the HTTP request pipeline.
        app.UseStaticFiles()
        app.UseRouting()
        
        app.MapControllers()
        app.MapGet("/api/health", Function() "API is running!")

        app.Run()
    End Sub
End Module
